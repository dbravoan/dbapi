<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Infrastructure\Persistence;

use Dbapi\Blogging\Tag\Domain\Tag;
use Dbapi\Blogging\Tag\Domain\TagId;
use Dbapi\Blogging\Tag\Domain\TagName;
use Dbapi\Blogging\Tag\Domain\TagRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

use function Lambdish\Phunctional\map;

final class EloquentTagRepository extends EloquentRepository implements TagRepository
{
    private static array $toEloquentFields = [
        'id' => 'id',
        'name' => 'name',
        'slug' => 'slug',
    ];

    public function __construct(Model $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(Tag $model): void
    {
        $this->model->updateOrCreate(
            ['id' => $model->id()->value()],
            $model->toPrimitives()
        );

        $this->publishEvents($model);
    }

    public function remove(TagId $id): void
    {
        $this->model->destroy($id->value());
    }

    public function search(TagId $id): ?Tag
    {
        $model = $this->model->find($id->value());

        return $model ? $this->toDomain($model->toArray()) : null;
    }

    public function searchByName(TagName $name): ?Tag
    {
        $model = $this->model->where('name', $name->value())->first();

        return $model ? $this->toDomain($model->toArray()) : null;
    }

    public function searchByCriteria(Criteria $criteria): array
    {
        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, self::$toEloquentFields);
        $query = $this->model->newQuery();

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        $results = $query->get()->toArray();

        return map(fn(array $row) => $this->toDomain($row), $results);
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

    private function toDomain(array $primitives): Tag
    {
        // Add slug to primitives if missing (fallback for existing records)
        if (!isset($primitives['slug']) && isset($primitives['name'])) {
            $primitives['slug'] = \Illuminate\Support\Str::slug($primitives['name']);
        }

        // The generator might have created different primitive names, let's assume Tag Domain expects:
        return Tag::fromPrimitives([
            'id' => $primitives['id'],
            'name' => $primitives['name'],
            'slug' => $primitives['slug'] ?? '',
        ]);
    }
}
