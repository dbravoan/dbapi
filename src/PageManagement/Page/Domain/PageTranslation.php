<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Domain;

/**
 * Holds all translatable fields for a Page in a specific language.
 * Stored in the intermediate {tenant}_page_translations table.
 */
final readonly class PageTranslation
{
    public function __construct(
        public string  $languageCode,
        public string  $slug,
        public string  $title,
        public array   $content,
        // SEO / meta fields
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $seoKeywords,
        public ?string $canonicalUrl,
        // Open Graph
        public ?string $ogTitle,
        public ?string $ogDescription,
        public ?string $ogImage,
        // Structured data (JSON-LD)
        public ?array  $structuredData,
    ) {}

    public static function fromPrimitives(array $data): self
    {
        return new self(
            languageCode:   $data['language_code'],
            slug:           $data['slug'],
            title:          $data['title'],
            content:        is_array($data['content']) ? $data['content'] : json_decode($data['content'], true, 512, JSON_THROW_ON_ERROR),
            seoTitle:       $data['seo_title'] ?? null,
            seoDescription: $data['seo_description'] ?? null,
            seoKeywords:    $data['seo_keywords'] ?? null,
            canonicalUrl:   $data['canonical_url'] ?? null,
            ogTitle:        $data['og_title'] ?? null,
            ogDescription:  $data['og_description'] ?? null,
            ogImage:        $data['og_image'] ?? null,
            structuredData: isset($data['structured_data'])
                ? (is_array($data['structured_data'])
                    ? $data['structured_data']
                    : json_decode($data['structured_data'], true, 512, JSON_THROW_ON_ERROR))
                : null,
        );
    }

    public function toPrimitives(string $pageId): array
    {
        return [
            'page_id'         => $pageId,
            'language_code'   => $this->languageCode,
            'slug'            => $this->slug,
            'title'           => $this->title,
            'content'         => $this->content,
            'seo_title'       => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_keywords'    => $this->seoKeywords,
            'canonical_url'   => $this->canonicalUrl,
            'og_title'        => $this->ogTitle,
            'og_description'  => $this->ogDescription,
            'og_image'        => $this->ogImage,
            'structured_data' => $this->structuredData,
        ];
    }
}
