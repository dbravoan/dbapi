<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Category\Tests\Domain;

use Dbapi\Blogging\Category\Domain\Category;
use Dbapi\Blogging\Category\Domain\CategoryCreatedDomainEvent;
use Dbapi\Blogging\Category\Domain\CategoryId;
use Dbapi\Blogging\Category\Domain\CategoryName;
use Dbapi\Blogging\Category\Domain\CategoryRenamedDomainEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    #[Test]
    public function it_should_create_a_category(): void
    {
        $id = CategoryId::random();
        $name = new CategoryName('Test Name');

        $category = Category::create($id, $name);

        $this->assertEquals($id, $category->id());
        $this->assertEquals($name, $category->name());

        $events = $category->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CategoryCreatedDomainEvent::class, $events[0]);
        $this->assertSame($id->value(), $events[0]->aggregateId());
        $this->assertSame('Test Name', $events[0]->name());
    }

    #[Test]
    public function it_should_record_a_renamed_event_when_name_changes(): void
    {
        $category = Category::create(CategoryId::random(), new CategoryName('Old'));
        $category->pullDomainEvents();

        $category->rename(new CategoryName('New'));

        $events = $category->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CategoryRenamedDomainEvent::class, $events[0]);
        $this->assertSame('New', $events[0]->name());
        $this->assertSame('New', $category->name()->value());
    }

    #[Test]
    public function rename_should_be_a_no_op_when_name_is_unchanged(): void
    {
        $category = Category::create(CategoryId::random(), new CategoryName('Same'));
        $category->pullDomainEvents();

        $category->rename(new CategoryName('Same'));

        $this->assertCount(0, $category->pullDomainEvents());
    }
}
