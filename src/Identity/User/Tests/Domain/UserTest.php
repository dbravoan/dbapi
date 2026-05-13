<?php

declare(strict_types=1);

namespace Dbapi\Identity\User\Tests\Domain;

use Dbapi\Identity\User\Domain\User;
use Dbapi\Identity\User\Domain\UserCreatedDomainEvent;
use Dbapi\Identity\User\Domain\UserId;
use Dbapi\Identity\User\Domain\UserName;
use Dbapi\Identity\User\Domain\UserRenamedDomainEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[Test]
    public function it_should_create_a_user(): void
    {
        $id = UserId::random();
        $name = new UserName('Test Name');

        $user = User::create($id, $name);

        $this->assertEquals($id, $user->id());
        $this->assertEquals($name, $user->name());

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreatedDomainEvent::class, $events[0]);
        $this->assertSame($id->value(), $events[0]->aggregateId());
        $this->assertSame('Test Name', $events[0]->name());
    }

    #[Test]
    public function it_should_record_a_renamed_event_when_name_changes(): void
    {
        $user = User::create(UserId::random(), new UserName('Old'));
        $user->pullDomainEvents();

        $user->rename(new UserName('New'));

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRenamedDomainEvent::class, $events[0]);
        $this->assertSame('New', $events[0]->name());
        $this->assertSame('New', $user->name()->value());
    }

    #[Test]
    public function rename_should_be_a_no_op_when_name_is_unchanged(): void
    {
        $user = User::create(UserId::random(), new UserName('Same'));
        $user->pullDomainEvents();

        $user->rename(new UserName('Same'));

        $this->assertCount(0, $user->pullDomainEvents());
    }
}
