<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Application\Create;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final readonly class CreatePostCommand implements Command
{
    /** @param string[] $tagNames */
    public function __construct(
        private string $id,
        private string $title,
        private string $content,
        private string $language,
        private ?string $seoTitle,
        private ?string $seoDescription,
        private ?string $seoKeywords,
        private ?string $canonicalUrl,
        private ?string $ogTitle,
        private ?string $ogDescription,
        private ?string $ogImage,
        private ?string $category,
        private array $tagNames = []
    ) {}

    public function id(): string { return $this->id; }
    public function title(): string { return $this->title; }
    public function content(): string { return $this->content; }
    public function language(): string { return $this->language; }
    public function seoTitle(): ?string { return $this->seoTitle; }
    public function seoDescription(): ?string { return $this->seoDescription; }
    public function seoKeywords(): ?string { return $this->seoKeywords; }
    public function canonicalUrl(): ?string { return $this->canonicalUrl; }
    public function ogTitle(): ?string { return $this->ogTitle; }
    public function ogDescription(): ?string { return $this->ogDescription; }
    public function ogImage(): ?string { return $this->ogImage; }
    public function category(): ?string { return $this->category; }
    public function tagNames(): array { return $this->tagNames; }
}
