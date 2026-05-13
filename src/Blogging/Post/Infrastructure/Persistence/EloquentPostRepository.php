<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Infrastructure\Persistence;

use Dbapi\Blogging\Post\Domain\Post;
use Dbapi\Blogging\Post\Domain\PostId;
use Dbapi\Blogging\Post\Domain\PostRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function Lambdish\Phunctional\map;

final class EloquentPostRepository extends EloquentRepository implements PostRepository
{
    private static array $toEloquentFields = [
        'id' => 'p.id',
        'title' => 'pt.title',
        'slug' => 'pt.slug',
        'language' => 'pt.language_code',
        'category_id' => 'p.category_id',
    ];

    public function __construct(Model $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(Post $model): void
    {
        DB::transaction(function () use ($model) {
            $tagIds = $model->tagIds();

            $this->model->updateOrCreate(
                ['id' => $model->id()->value()],
                $model->toMainPrimitives()
            );

            $translationTable = $this->translationTableName();
            DB::table($translationTable)->updateOrInsert(
                [
                    'post_id' => $model->id()->value(),
                    'language_code' => $model->language()->value(),
                ],
                [
                    'title' => $model->title()->value(),
                    'slug' => $model->slug()->value(),
                    'content' => $model->content()->value(),
                    'seo_title' => $model->seoTitle(),
                    'seo_description' => $model->seoDescription(),
                    'seo_keywords' => $model->seoKeywords(),
                    'canonical_url' => $model->canonicalUrl(),
                    'og_title' => $model->ogTitle(),
                    'og_description' => $model->ogDescription(),
                    'og_image' => $model->ogImage(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Handle Tags Pivot
            $appId = config('database.tenant.app_id');
            $pivotTable = "{$appId}_post_tag";

            DB::table($pivotTable)->where('post_id', $model->id()->value())->delete();
            
            foreach ($tagIds as $tagId) {
                DB::table($pivotTable)->insert([
                    'post_id' => $model->id()->value(),
                    'tag_id' => $tagId
                ]);
            }
        });

        $this->publishEvents($model);
    }

    public function remove(PostId $id): void
    {
        DB::transaction(function () use ($id) {
            DB::table($this->translationTableName())->where('post_id', $id->value())->delete();
            $this->model->destroy($id->value());
        });
    }

    public function search(PostId $id, string $languageCode = 'en'): ?Post
    {
        $model = $this->model
            ->from($this->model->getTable() . ' as p')
            ->join($this->translationTableName() . ' as pt', 'pt.post_id', '=', 'p.id')
            ->where('p.id', $id->value())
            ->where('pt.language_code', $languageCode)
            ->select(['p.*', 'pt.title', 'pt.slug', 'pt.content', DB::raw('pt.language_code as language'), 'pt.seo_title', 'pt.seo_description', 'pt.seo_keywords', 'pt.canonical_url', 'pt.og_title', 'pt.og_description', 'pt.og_image'])
            ->first();

        // Backward compatibility for tenants still storing localized fields in {app}_posts.
        if (!$model) {
            $legacy = $this->model->find($id->value());
            if ($legacy) {
                $legacyData = $legacy->toArray();
                if (isset($legacyData['title'], $legacyData['slug'], $legacyData['content'])) {
                    $legacyData['language'] = $legacyData['language'] ?? $languageCode;

                    $appId = config('database.tenant.app_id');
                    $tagIds = DB::table("{$appId}_post_tag")
                        ->where('post_id', $id->value())
                        ->pluck('tag_id')
                        ->toArray();

                    $legacyData['tag_ids'] = $tagIds;

                    return $this->toDomain($legacyData);
                }
            }
        }

        if (!$model) return null;

        $appId = config('database.tenant.app_id');
        $pivotTable = "{$appId}_post_tag";
        $tagIds = DB::table($pivotTable)
            ->where('post_id', $id->value())
            ->pluck('tag_id')
            ->toArray();

        $primitives = $model->toArray();
        $primitives['tag_ids'] = $tagIds;

        return $this->toDomain($primitives);
    }

    public function searchByCriteria(Criteria $criteria, string $languageCode = 'en'): array
    {
        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, self::$toEloquentFields);
        $query = $this->model
            ->from($this->model->getTable() . ' as p')
            ->join($this->translationTableName() . ' as pt', 'pt.post_id', '=', 'p.id')
            ->where('pt.language_code', $languageCode)
            ->select(['p.*', 'pt.title', 'pt.slug', 'pt.content', DB::raw('pt.language_code as language'), 'pt.seo_title', 'pt.seo_description', 'pt.seo_keywords', 'pt.canonical_url', 'pt.og_title', 'pt.og_description', 'pt.og_image']);

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        $results = $query->get();

        return map(function($model) {
            $appId = config('database.tenant.app_id');
            $tagIds = DB::table("{$appId}_post_tag")
                ->where('post_id', $model->id)
                ->pluck('tag_id')
                ->toArray();
            
            $primitives = $model->toArray();
            $primitives['tag_ids'] = $tagIds;
            return $this->toDomain($primitives);
        }, $results);
    }

    public function countByCriteria(Criteria $criteria, string $languageCode = 'en'): int
    {
        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, self::$toEloquentFields);

        // Mirror searchByCriteria(): the field map references `pt.title`/`pt.slug`/
        // `pt.language_code`, so the count query must alias the main table and
        // join the translations table the same way; otherwise any filter on a
        // translatable field would explode at the SQL layer ("unknown column pt.title").
        $query = $this->model
            ->from($this->model->getTable() . ' as p')
            ->join($this->translationTableName() . ' as pt', 'pt.post_id', '=', 'p.id')
            ->where('pt.language_code', $languageCode);

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        return $query->count();
    }

    private function toDomain(array $primitives): Post
    {
        return Post::fromPrimitives($primitives);
    }

    private function translationTableName(): string
    {
        $appId = config('database.tenant.app_id');
        return "{$appId}_post_translations";
    }
}
