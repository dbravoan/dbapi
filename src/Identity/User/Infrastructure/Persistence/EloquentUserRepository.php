<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Infrastructure\Persistence;

use Dbapi\Identity\User\Domain\User;
use Dbapi\Identity\User\Domain\UserId;
use Dbapi\Identity\User\Domain\UserRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

use function Lambdish\Phunctional\map;

final class EloquentUserRepository extends EloquentRepository implements UserRepository
{
    private static array $toEloquentFields = [
        'id' => 'id',
        'name' => 'name',
    ];

    public function __construct(Model $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(User $model): void
    {
        $this->model->updateOrCreate(
            ['id' => $model->id()->value()],
            $model->toPrimitives()
        );

        $this->publishEvents($model);
    }

    public function remove(UserId $id): void
    {
        $this->model->destroy($id->value());
    }

    public function search(UserId $id): ?User
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

    private function toDomain(array $primitives): User
    {
        return User::fromPrimitives($primitives);
    }
}
