<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Application\Response;

use Dbapi\PageManagement\Page\Domain\Page;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\Response;

final class PageResponse implements Response
{
    private function __construct(
        private readonly string  $id,
        private readonly string  $status,
        // Translation
        private readonly string  $languageCode,
        private readonly string  $slug,
        private readonly string  $title,
        private readonly array   $content,
        // SEO
        private readonly ?string $seoTitle,
        private readonly ?string $seoDescription,
        private readonly ?string $seoKeywords,
        private readonly ?string $canonicalUrl,
        // OG
        private readonly ?string $ogTitle,
        private readonly ?string $ogDescription,
        private readonly ?string $ogImage,
        // Structured data
        private readonly ?array  $structuredData,
    ) {}

    public static function fromAggregate(Page $page): self
    {
        $t = $page->translation();

        return new self(
            id:             $page->id()->value(),
            status:         $page->status()->value(),
            languageCode:   $t->languageCode,
            slug:           $t->slug,
            title:          $t->title,
            content:        $t->content,
            seoTitle:       $t->seoTitle,
            seoDescription: $t->seoDescription,
            seoKeywords:    $t->seoKeywords,
            canonicalUrl:   $t->canonicalUrl,
            ogTitle:        $t->ogTitle,
            ogDescription:  $t->ogDescription,
            ogImage:        $t->ogImage,
            structuredData: $t->structuredData,
        );
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status,
            'language_code'  => $this->languageCode,
            'slug'           => $this->slug,
            'title'          => $this->title,
            'content'        => $this->content,
            'seo'            => [
                'title'          => $this->seoTitle,
                'description'    => $this->seoDescription,
                'keywords'       => $this->seoKeywords,
                'canonical_url'  => $this->canonicalUrl,
            ],
            'og'             => [
                'title'          => $this->ogTitle,
                'description'    => $this->ogDescription,
                'image'          => $this->ogImage,
            ],
            'structured_data' => $this->structuredData,
        ];
    }
}
