<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Infrastructure\Persistence;

use Dbapi\Blogging\Category\Domain\Category;
use Dbapi\Blogging\Category\Domain\CategoryId;
use Dbapi\Blogging\Category\Domain\CategoryRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

use function Lambdish\Phunctional\map;

final class EloquentCategoryRepository extends EloquentRepository implements CategoryRepository
{
    private static array $toEloquentFields = [
        'id' => 'id',
        'name' => 'name',
    ];

    public function __construct(Model $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(Category $model): void
    {
        $this->model->updateOrCreate(
            ['id' => $model->id()->value()],
            $model->toPrimitives()
        );

        $this->publishEvents($model);
    }

    public function remove(CategoryId $id): void
    {
        $this->model->destroy($id->value());
    }

    public function search(CategoryId $id): ?Category
    {
        $model = $this->model->find($id->value());

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

    private function toDomain(array $primitives): Category
    {
        return Category::fromPrimitives($primitives);
    }
}
