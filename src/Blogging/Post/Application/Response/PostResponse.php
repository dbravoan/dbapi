<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\Response;

use Dbapi\Blogging\Post\Domain\Post;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final readonly class PostResponse implements Response
{
    public function __construct(
        private string $id,
        private string $title,
        private string $slug,
        private string $content,
        private string $language,
        private ?string $categoryId,
        private ?string $seoTitle,
        private ?string $seoDescription,
        private ?string $seoKeywords,
        private ?string $canonicalUrl,
        private ?string $ogTitle,
        private ?string $ogDescription,
        private ?string $ogImage,
    ) {}

    public static function fromAggregate(Post $post): self
    {
        return new self(
            $post->id()->value(),
            $post->title()->value(),
            $post->slug()->value(),
            $post->content()->value(),
            $post->language()->value(),
            $post->categoryId(),
            $post->seoTitle(),
            $post->seoDescription(),
            $post->seoKeywords(),
            $post->canonicalUrl(),
            $post->ogTitle(),
            $post->ogDescription(),
            $post->ogImage(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'content'     => $this->content,
            'language'    => $this->language,
            'category_id' => $this->categoryId,
            'seo' => [
                'title'         => $this->seoTitle,
                'description'   => $this->seoDescription,
                'keywords'      => $this->seoKeywords,
                'canonical_url' => $this->canonicalUrl,
            ],
            'og' => [
                'title'       => $this->ogTitle,
                'description' => $this->ogDescription,
                'image'       => $this->ogImage,
            ],
        ];
    }
}
