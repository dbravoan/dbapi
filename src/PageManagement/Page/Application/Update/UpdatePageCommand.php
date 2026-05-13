<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Application\Update;

use Dba\DddSkeleton\Shared\Domain\Bus\Command\Command;

final class UpdatePageCommand implements Command
{
    public function __construct(
        private readonly string  $id,
        private readonly ?string $status,
        // Translation fields (upsert for the given language)
        private readonly string  $languageCode,
        private readonly ?string $slug,
        private readonly ?string $title,
        private readonly ?array  $content,
        // SEO fields
        private readonly ?string $seoTitle,
        private readonly ?string $seoDescription,
        private readonly ?string $seoKeywords,
        private readonly ?string $canonicalUrl,
        // Open Graph
        private readonly ?string $ogTitle,
        private readonly ?string $ogDescription,
        private readonly ?string $ogImage,
        // Structured data
        private readonly ?array  $structuredData,
    ) {}

    public function id(): string              { return $this->id; }
    public function status(): ?string         { return $this->status; }
    public function languageCode(): string    { return $this->languageCode; }
    public function slug(): ?string           { return $this->slug; }
    public function title(): ?string          { return $this->title; }
    public function content(): ?array         { return $this->content; }
    public function seoTitle(): ?string       { return $this->seoTitle; }
    public function seoDescription(): ?string { return $this->seoDescription; }
    public function seoKeywords(): ?string    { return $this->seoKeywords; }
    public function canonicalUrl(): ?string   { return $this->canonicalUrl; }
    public function ogTitle(): ?string        { return $this->ogTitle; }
    public function ogDescription(): ?string  { return $this->ogDescription; }
    public function ogImage(): ?string        { return $this->ogImage; }
    public function structuredData(): ?array  { return $this->structuredData; }
}
