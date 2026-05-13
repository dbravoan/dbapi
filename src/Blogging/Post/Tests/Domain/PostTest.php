<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Tests\Domain;

use Dbapi\Blogging\Post\Domain\Post;
use Dbapi\Blogging\Post\Domain\PostContent;
use Dbapi\Blogging\Post\Domain\PostCreatedDomainEvent;
use Dbapi\Blogging\Post\Domain\PostId;
use Dbapi\Blogging\Post\Domain\PostLanguage;
use Dbapi\Blogging\Post\Domain\PostName;
use Dbapi\Blogging\Post\Domain\PostSlug;
use Dbapi\Blogging\Post\Domain\PostUpdatedDomainEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PostTest extends TestCase
{
    private function makePost(): Post
    {
        return Post::create(
            PostId::random(),
            new PostName('Test Post'),
            new PostSlug('test-post'),
            new PostContent('Some content here.'),
            new PostLanguage('en'),
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            ['tag-1'],
        );
    }

    #[Test]
    public function it_should_create_a_post(): void
    {
        $post = $this->makePost();

        $this->assertSame('Test Post', $post->title()->value());
        $this->assertSame(['tag-1'], $post->tagIds());
    }

    #[Test]
    public function it_should_record_a_post_created_event_on_creation(): void
    {
        $post = $this->makePost();

        $events = $post->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PostCreatedDomainEvent::class, $events[0]);
        $this->assertSame('Test Post', $events[0]->title());
    }

    #[Test]
    public function update_records_a_post_updated_event_and_not_a_created_event(): void
    {
        $post = $this->makePost();
        $post->pullDomainEvents(); // discard creation event

        $post->update(
            new PostName('New title'),
            new PostSlug('new-title'),
            new PostContent('New content'),
            new PostLanguage('en'),
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            ['tag-1', 'tag-2'],
        );

        $events = $post->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PostUpdatedDomainEvent::class, $events[0]);
        $this->assertSame('New title', $events[0]->title());
        $this->assertSame('en', $events[0]->language());
        $this->assertSame(['tag-1', 'tag-2'], $post->tagIds());
    }

    #[Test]
    public function to_primitives_round_trips_main_and_translation_columns(): void
    {
        $post = $this->makePost();

        $primitives = $post->toPrimitives();
        $rehydrated = Post::fromPrimitives($primitives);

        $this->assertSame($post->id()->value(), $rehydrated->id()->value());
        $this->assertSame($post->title()->value(), $rehydrated->title()->value());
        $this->assertSame($post->language()->value(), $rehydrated->language()->value());
        $this->assertSame($post->tagIds(), $rehydrated->tagIds());

        $this->assertArrayHasKey('id', $post->toMainPrimitives());
        $this->assertArrayHasKey('category_id', $post->toMainPrimitives());
        $this->assertArrayNotHasKey('title', $post->toMainPrimitives());

        $this->assertArrayHasKey('title', $post->toTranslationPrimitives());
        $this->assertArrayHasKey('slug', $post->toTranslationPrimitives());
        $this->assertArrayHasKey('language', $post->toTranslationPrimitives());
    }
}
