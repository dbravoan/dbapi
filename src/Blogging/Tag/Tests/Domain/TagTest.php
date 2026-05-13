<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Tag\Tests\Domain;

use Dbapi\Blogging\Tag\Domain\Tag;
use Dbapi\Blogging\Tag\Domain\TagCreatedDomainEvent;
use Dbapi\Blogging\Tag\Domain\TagId;
use Dbapi\Blogging\Tag\Domain\TagName;
use Dbapi\Blogging\Tag\Domain\TagRenamedDomainEvent;
use Dbapi\Blogging\Tag\Domain\TagSlug;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TagTest extends TestCase
{
    #[Test]
    public function it_should_create_a_tag(): void
    {
        $id   = TagId::random();
        $name = new TagName('Test Tag');
        $slug = new TagSlug('test-tag');

        $tag = Tag::create($id, $name, $slug);

        $this->assertEquals($id, $tag->id());
        $this->assertEquals($name, $tag->name());
        $this->assertEquals($slug, $tag->slug());
    }

    #[Test]
    public function it_should_record_a_tag_created_domain_event_on_creation(): void
    {
        $id   = TagId::random();
        $name = new TagName('Test Tag');
        $slug = new TagSlug('test-tag');

        $tag = Tag::create($id, $name, $slug);

        $events = $tag->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TagCreatedDomainEvent::class, $events[0]);
        $this->assertSame($id->value(), $events[0]->aggregateId());
        $this->assertSame('Test Tag', $events[0]->name());
    }

    #[Test]
    public function it_should_record_a_renamed_event_when_name_changes(): void
    {
        $tag = Tag::create(TagId::random(), new TagName('Old Name'), new TagSlug('old-name'));
        $tag->pullDomainEvents(); // discard creation event

        $tag->rename(new TagName('New Name'));

        $events = $tag->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TagRenamedDomainEvent::class, $events[0]);
        $this->assertSame('New Name', $events[0]->name());
        $this->assertSame('New Name', $tag->name()->value());
    }

    #[Test]
    public function rename_should_be_a_no_op_when_name_is_unchanged(): void
    {
        $tag = Tag::create(TagId::random(), new TagName('Same'), new TagSlug('same'));
        $tag->pullDomainEvents(); // discard creation event

        $tag->rename(new TagName('Same'));

        $this->assertCount(0, $tag->pullDomainEvents());
    }
}
