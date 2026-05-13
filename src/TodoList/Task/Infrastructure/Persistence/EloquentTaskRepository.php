<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Infrastructure\Persistence;

use Dbapi\TodoList\Task\Domain\Task;
use Dbapi\TodoList\Task\Domain\TaskId;
use Dbapi\TodoList\Task\Domain\TaskRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

use function Lambdish\Phunctional\map;

final class EloquentTaskRepository extends EloquentRepository implements TaskRepository
{
    private static array $toEloquentFields = [
        'id'       => 'id',
        'title'    => 'title',
        'status'   => 'status',
        'priority' => 'priority',
    ];

    public function __construct(Model $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(Task $task): void
    {
        $this->model->updateOrCreate(
            ['id' => $task->id()->value()],
            $task->toPrimitives()
        );

        $this->publishEvents($task);
    }

    public function remove(TaskId $id): void
    {
        $this->model->destroy($id->value());
    }

    public function search(TaskId $id): ?Task
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

        return map(fn(array $row) => $this->toDomain($row), $query->get()->toArray());
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

    private function toDomain(array $primitives): Task
    {
        return Task::fromPrimitives($primitives);
    }
}
