<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Domain;

use Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot;

final class Post extends AggregateRoot
{
    /** @param string[] $tagIds */
    public function __construct(
        private readonly PostId $id,
        private PostName     $title,
        private PostSlug     $slug,
        private PostContent  $content,
        private PostLanguage $language,
        private ?string      $seoTitle,
        private ?string      $seoDescription,
        private ?string      $seoKeywords,
        private ?string      $canonicalUrl,
        private ?string      $ogTitle,
        private ?string      $ogDescription,
        private ?string      $ogImage,
        private ?string      $categoryId,
        private array        $tagIds = [],
    ) {}

    /** @param string[] $tagIds */
    public static function create(
        PostId       $id,
        PostName     $title,
        PostSlug     $slug,
        PostContent  $content,
        PostLanguage $language,
        ?string      $seoTitle,
        ?string      $seoDescription,
        ?string      $seoKeywords,
        ?string      $canonicalUrl,
        ?string      $ogTitle,
        ?string      $ogDescription,
        ?string      $ogImage,
        ?string      $categoryId,
        array        $tagIds = [],
    ): self {
        $post = new self(
            $id,
            $title,
            $slug,
            $content,
            $language,
            $seoTitle,
            $seoDescription,
            $seoKeywords,
            $canonicalUrl,
            $ogTitle,
            $ogDescription,
            $ogImage,
            $categoryId,
            $tagIds
        );
        $post->record(new PostCreatedDomainEvent($id->value(), $title->value()));

        return $post;
    }

    public function id(): PostId { return $this->id; }
    public function title(): PostName { return $this->title; }
    public function slug(): PostSlug { return $this->slug; }
    public function content(): PostContent { return $this->content; }
    public function language(): PostLanguage { return $this->language; }
    public function seoTitle(): ?string { return $this->seoTitle; }
    public function seoDescription(): ?string { return $this->seoDescription; }
    public function seoKeywords(): ?string { return $this->seoKeywords; }
    public function canonicalUrl(): ?string { return $this->canonicalUrl; }
    public function ogTitle(): ?string { return $this->ogTitle; }
    public function ogDescription(): ?string { return $this->ogDescription; }
    public function ogImage(): ?string { return $this->ogImage; }
    public function categoryId(): ?string { return $this->categoryId; }
    public function tagIds(): array { return $this->tagIds; }

    /**
     * Apply an in-place update of the mutable fields and record a PostUpdatedDomainEvent.
     * Used by the Update use case so updates do NOT re-emit PostCreatedDomainEvent.
     *
     * @param string[] $tagIds
     */
    public function update(
        PostName     $title,
        PostSlug     $slug,
        PostContent  $content,
        PostLanguage $language,
        ?string      $seoTitle,
        ?string      $seoDescription,
        ?string      $seoKeywords,
        ?string      $canonicalUrl,
        ?string      $ogTitle,
        ?string      $ogDescription,
        ?string      $ogImage,
        ?string      $categoryId,
        array        $tagIds,
    ): void {
        $this->title          = $title;
        $this->slug           = $slug;
        $this->content        = $content;
        $this->language       = $language;
        $this->seoTitle       = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->seoKeywords    = $seoKeywords;
        $this->canonicalUrl   = $canonicalUrl;
        $this->ogTitle        = $ogTitle;
        $this->ogDescription  = $ogDescription;
        $this->ogImage        = $ogImage;
        $this->categoryId     = $categoryId;
        $this->tagIds         = $tagIds;

        $this->record(new PostUpdatedDomainEvent(
            $this->id->value(),
            $title->value(),
            $language->value(),
        ));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            new PostId($primitives['id']),
            new PostName($primitives['title']),
            new PostSlug($primitives['slug']),
            new PostContent($primitives['content']),
            new PostLanguage($primitives['language']),
            $primitives['seo_title'] ?? null,
            $primitives['seo_description'] ?? null,
            $primitives['seo_keywords'] ?? null,
            $primitives['canonical_url'] ?? null,
            $primitives['og_title'] ?? null,
            $primitives['og_description'] ?? null,
            $primitives['og_image'] ?? null,
            $primitives['category_id'] ?? null,
            $primitives['tag_ids'] ?? []
        );
    }

    /**
     * Full primitives, including translatable fields. Persisted by the repository,
     * which splits the payload into main + translation rows. See
     * EloquentPostRepository::save() for the columns that go to each table.
     */
    public function toPrimitives(): array
    {
        return array_merge($this->toMainPrimitives(), $this->toTranslationPrimitives(), [
            'tag_ids' => $this->tagIds,
        ]);
    }

    /** Columns persisted in the main {tenant}_posts table. */
    public function toMainPrimitives(): array
    {
        return [
            'id'          => $this->id->value(),
            'category_id' => $this->categoryId,
        ];
    }

    /** Columns persisted in the {tenant}_post_translations row for the current language. */
    public function toTranslationPrimitives(): array
    {
        return [
            'title'           => $this->title->value(),
            'slug'            => $this->slug->value(),
            'content'         => $this->content->value(),
            'language'        => $this->language->value(),
            'seo_title'       => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_keywords'    => $this->seoKeywords,
            'canonical_url'   => $this->canonicalUrl,
            'og_title'        => $this->ogTitle,
            'og_description'  => $this->ogDescription,
            'og_image'        => $this->ogImage,
        ];
    }
}
