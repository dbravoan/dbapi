<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\Update;

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
use Illuminate\Support\Str;

final class UpdatePostCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly PostRepository $repository,
        private readonly TagRepository  $tagRepository,
    ) {}

    public function __invoke(UpdatePostCommand $command): void
    {
        $id = new PostId($command->id());
        $post = $this->repository->search($id, $command->language());

        if (null === $post) {
            return;
        }

        $title = $command->title() !== null
            ? new PostName($command->title())
            : $post->title();

        $content = $command->content() !== null
            ? new PostContent($command->content())
            : $post->content();

        $slug = $command->title() !== null
            ? new PostSlug(Str::slug($command->title()))
            : $post->slug();

        $tagIds = $post->tagIds();
        if ($command->tagNames() !== null) {
            $tagIds = $this->resolveTagIds($command->tagNames());
        }

        $post->update(
            $title,
            $slug,
            $content,
            new PostLanguage($command->language()),
            $command->seoTitle() ?? $post->seoTitle(),
            $command->seoDescription() ?? $post->seoDescription(),
            $command->seoKeywords() ?? $post->seoKeywords(),
            $command->canonicalUrl() ?? $post->canonicalUrl(),
            $command->ogTitle() ?? $post->ogTitle(),
            $command->ogDescription() ?? $post->ogDescription(),
            $command->ogImage() ?? $post->ogImage(),
            $command->categoryId() ?? $post->categoryId(),
            $tagIds,
        );

        $this->repository->save($post);
    }

    /**
     * @param  string[] $tagNames
     * @return string[]
     */
    private function resolveTagIds(array $tagNames): array
    {
        $tagIds = [];
        foreach ($tagNames as $nameString) {
            $tagName = new TagName($nameString);
            $existingTag = $this->tagRepository->searchByName($tagName);

            if ($existingTag) {
                $tagIds[] = $existingTag->id()->value();
                continue;
            }

            $newTagId = Uuid::random();
            $newTag = Tag::create(
                new TagId($newTagId->value()),
                $tagName,
                new TagSlug(Str::slug($nameString)),
            );
            $this->tagRepository->save($newTag);
            $tagIds[] = $newTagId->value();
        }

        return $tagIds;
    }
}
