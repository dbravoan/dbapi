<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Infrastructure\Persistence;

use Dbapi\Language\Language\Domain\Language;
use Dbapi\Language\Language\Domain\LanguageCode;
use Dbapi\Language\Language\Domain\LanguageId;
use Dbapi\Language\Language\Domain\LanguageRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

use function Lambdish\Phunctional\map;

final class EloquentLanguageRepository extends EloquentRepository implements LanguageRepository
{
    private static array $toEloquentFields = [
        'id'         => 'id',
        'code'       => 'code',
        'name'       => 'name',
        'is_default' => 'is_default',
        'is_active'  => 'is_active',
    ];

    public function __construct(Model $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(Language $language): void
    {
        $this->model->updateOrCreate(
            ['id' => $language->id()->value()],
            $language->toPrimitives()
        );

        $this->publishEvents($language);
    }

    public function remove(LanguageId $id): void
    {
        $this->model->destroy($id->value());
    }

    public function search(LanguageId $id): ?Language
    {
        $model = $this->model->find($id->value());

        return $model ? $this->toDomain($model->toArray()) : null;
    }

    public function searchByCode(LanguageCode $code): ?Language
    {
        $model = $this->model->where('code', $code->value())->first();

        return $model ? $this->toDomain($model->toArray()) : null;
    }

    public function searchAll(): array
    {
        return map(
            fn (array $row) => $this->toDomain($row),
            $this->model->orderBy('name')->get()->toArray()
        );
    }

    public function searchByCriteria(Criteria $criteria): array
    {
        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, self::$toEloquentFields);
        $query = $this->model->newQuery();

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        return map(fn (array $row) => $this->toDomain($row), $query->get()->toArray());
    }

    public function countByCriteria(Criteria $criteria): int
    {
        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, self::$toEloquentFields);
        $query = $this->model->newQuery();

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        return $query->count();
    }

    private function toDomain(array $primitives): Language
    {
        return Language::fromPrimitives($primitives);
    }
}
