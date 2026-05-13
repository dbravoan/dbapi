<?php

declare(strict_types=1);

namespace Dbapi\TodoList\Task\Tests\Domain;

use Dbapi\TodoList\Task\Domain\Task;
use Dbapi\TodoList\Task\Domain\TaskCreatedDomainEvent;
use Dbapi\TodoList\Task\Domain\TaskId;
use Dbapi\TodoList\Task\Domain\TaskPriority;
use Dbapi\TodoList\Task\Domain\TaskStatus;
use Dbapi\TodoList\Task\Domain\TaskTitle;
use Dbapi\TodoList\Task\Domain\TaskUpdatedDomainEvent;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    private const ID    = '550e8400-e29b-41d4-a716-446655440000';
    private const TITLE = 'Write unit tests';

    public function test_task_can_be_created(): void
    {
        $task = Task::create(
            new TaskId(self::ID),
            new TaskTitle(self::TITLE),
            TaskStatus::pending(),
            TaskPriority::medium(),
            'Some description',
        );

        $this->assertSame(self::ID,   $task->id()->value());
        $this->assertSame(self::TITLE, $task->title()->value());
        $this->assertSame('pending',  $task->status()->value());
        $this->assertSame('medium',   $task->priority()->value());
        $this->assertSame('Some description', $task->description());
    }

    public function test_task_create_records_domain_event(): void
    {
        $task = Task::create(
            new TaskId(self::ID),
            new TaskTitle(self::TITLE),
            TaskStatus::pending(),
            TaskPriority::high(),
            null,
        );

        $events = $task->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskCreatedDomainEvent::class, $events[0]);
    }

    public function test_task_roundtrips_through_primitives(): void
    {
        $original = Task::create(
            new TaskId(self::ID),
            new TaskTitle(self::TITLE),
            TaskStatus::inProgress(),
            TaskPriority::low(),
            'Description here',
        );

        $reconstituted = Task::fromPrimitives($original->toPrimitives());

        $this->assertSame($original->id()->value(),       $reconstituted->id()->value());
        $this->assertSame($original->title()->value(),    $reconstituted->title()->value());
        $this->assertSame($original->status()->value(),   $reconstituted->status()->value());
        $this->assertSame($original->priority()->value(), $reconstituted->priority()->value());
        $this->assertSame($original->description(),       $reconstituted->description());
    }

    public function test_invalid_status_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TaskStatus('invalid_status');
    }

    public function test_invalid_priority_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TaskPriority('ultra');
    }

    public function test_update_records_an_updated_event_and_not_a_created_event(): void
    {
        $task = Task::create(
            new TaskId(self::ID),
            new TaskTitle('Old'),
            TaskStatus::pending(),
            TaskPriority::low(),
            null,
        );
        $task->pullDomainEvents(); // discard creation event

        $task->update(
            new TaskTitle('New'),
            TaskStatus::inProgress(),
            TaskPriority::high(),
            'New description',
        );

        $events = $task->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskUpdatedDomainEvent::class, $events[0]);
        $this->assertSame('New', $events[0]->title());
        $this->assertSame('New', $task->title()->value());
        $this->assertSame('in_progress', $task->status()->value());
    }
}
