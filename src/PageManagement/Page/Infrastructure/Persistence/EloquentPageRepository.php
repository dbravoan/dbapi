<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Infrastructure\Persistence;

use Dbapi\PageManagement\Page\Domain\Page;
use Dbapi\PageManagement\Page\Domain\PageId;
use Dbapi\PageManagement\Page\Domain\PageRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function Lambdish\Phunctional\map;

final class EloquentPageRepository extends EloquentRepository implements PageRepository
{
    private static array $toEloquentFields = [
        'id'     => 'p.id',
        'status' => 'p.status',
        'slug'   => 'pt.slug',
        'title'  => 'pt.title',
    ];

    public function __construct(Model $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(Page $page): void
    {
        DB::transaction(function () use ($page) {
            // Upsert main page (non-translatable)
            $this->model->updateOrCreate(
                ['id' => $page->id()->value()],
                $page->toPrimitives()
            );

            // Upsert translation row for the given language
            $translationModel = $this->translationModel();
            $translationModel->updateOrCreate(
                [
                    'page_id'       => $page->id()->value(),
                    'language_code' => $page->translation()->languageCode,
                ],
                $page->translation()->toPrimitives($page->id()->value())
            );
        });

        $this->publishEvents($page);
    }

    public function remove(PageId $id): void
    {
        DB::transaction(function () use ($id) {
            $this->translationModel()->where('page_id', $id->value())->delete();
            $this->model->destroy($id->value());
        });
    }

    public function search(PageId $id, string $languageCode): ?Page
    {
        $record = $this->model
            ->join(
                $this->translationTableName() . ' as pt',
                'pt.page_id',
                '=',
                $this->model->getTable() . '.id'
            )
            ->where($this->model->getTable() . '.id', $id->value())
            ->where('pt.language_code', $languageCode)
            ->select([$this->model->getTable() . '.*', 'pt.*'])
            ->first();

        return $record ? $this->toDomain($record->toArray()) : null;
    }

    public function searchByCriteria(Criteria $criteria, string $languageCode): array
    {
        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, self::$toEloquentFields);

        $query = $this->model
            ->from($this->model->getTable() . ' as p')
            ->join(
                $this->translationTableName() . ' as pt',
                'pt.page_id',
                '=',
                'p.id'
            )
            ->where('pt.language_code', $languageCode)
            ->select(['p.*', 'pt.*']);

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        return map(fn (array $row) => $this->toDomain($row), $query->get()->toArray());
    }

    public function countByCriteria(Criteria $criteria, string $languageCode = 'en'): int
    {
        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, self::$toEloquentFields);

        // Mirror searchByCriteria(): the field map references `pt.title`/`pt.slug`,
        // so the count query must alias the main table and join the page
        // translations table — otherwise filters on translatable fields raise
        // "unknown column pt.title" at the SQL layer.
        $query = $this->model
            ->from($this->model->getTable() . ' as p')
            ->join(
                $this->translationTableName() . ' as pt',
                'pt.page_id',
                '=',
                'p.id'
            )
            ->where('pt.language_code', $languageCode);

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        return $query->count();
    }

    private function toDomain(array $primitives): Page
    {
        return Page::fromPrimitives($primitives);
    }

    private function translationModel(): Model
    {
        return new \App\Models\PageTranslation();
    }

    private function translationTableName(): string
    {
        return (new \App\Models\PageTranslation())->getTable();
    }
}
