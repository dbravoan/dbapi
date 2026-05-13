<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\Create;

use Dbapi\Blogging\Post\Domain\Post;
use Dbapi\Blogging\Post\Domain\PostContent;
use Dbapi\Blogging\Post\Domain\PostId;
use Dbapi\Blogging\Post\Domain\PostLanguage;
use Dbapi\Blogging\Post\Domain\PostName;
use Dbapi\Blogging\Post\Domain\PostRepository;
use Dbapi\Blogging\Post\Domain\PostSlug;
use Dbapi\Blogging\Tag\Domain\Tag;
use Dbapi\Blogging\Tag\Domain\TagId;
use Dbapi\Blogging\Tag\Domain\TagName;
use Dbapi\Blogging\Tag\Domain\TagRepository;
use Dbapi\Blogging\Tag\Domain\TagSlug;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;
use Dba\DddSkeleton\Shared\Domain\ValueObject\Uuid;
use Laravel\AI\Facades\AI;
use Illuminate\Support\Str;

final class CreatePostCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly PostRepository $repository,
        private readonly TagRepository $tagRepository
    ) {}

    public function __invoke(CreatePostCommand $command): void
    {
        $id = new PostId($command->id());
        $title = new PostName($command->title());
        $content = new PostContent($command->content());
        $language = new PostLanguage($command->language());
        $categoryId = $command->category(); // Simplified for now

        // 1. Handle Slug with AI if needed
        $slugValue = Str::slug($command->title());
        if (strlen($slugValue) < 5) {
             $slugValue = AI::chat("Generate a 3-word URL slug for: " . $command->title());
             $slugValue = Str::slug($slugValue);
        }
        $slug = new PostSlug($slugValue);

        // 2. Handle Tags (Create if not exists)
        $tagIds = [];
        $tagNames = $command->tagNames(); // Expecting array of strings

        foreach ($tagNames as $nameString) {
            $tagName = new TagName($nameString);
            $existingTag = $this->tagRepository->searchByName($tagName);

            if ($existingTag) {
                $tagIds[] = $existingTag->id()->value();
            } else {
                $newTagId = Uuid::random();
                $newTag = Tag::create(
                    new TagId($newTagId->value()),
                    $tagName,
                    new TagSlug(Str::slug($nameString))
                );
                $this->tagRepository->save($newTag);
                $tagIds[] = $newTagId->value();
            }
        }

        $post = Post::create(
            $id,
            $title,
            $slug,
            $content,
            $language,
            $command->seoTitle(),
            $command->seoDescription(),
            $command->seoKeywords(),
            $command->canonicalUrl(),
            $command->ogTitle(),
            $command->ogDescription(),
            $command->ogImage(),
            $categoryId,
            $tagIds
        );

        $this->repository->save($post);
    }
}
