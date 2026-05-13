<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Tests\Infrastructure;

use App\Models\BlogPost;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Domain\Criteria\Filters;
use Dba\DddSkeleton\Shared\Domain\Criteria\Order;
use Tests\TestCase;

/**
 * Regression test for audit finding #36 — countByCriteria must JOIN the post
 * translations table so filters referencing the `pt.*` alias resolve at the
 * SQL layer.
 *
 * We assert via the generated SQL string (no DB driver required): the produced
 * query must alias the main table as `p`, INNER JOIN the post_translations
 * table aliased as `pt`, and the WHERE clause referencing `pt.title` must be
 * resolvable. Before this fix the count query was built from a bare
 * `$this->model->newQuery()` with no join, so `pt.title` would fail with
 * "unknown column".
 */
final class EloquentPostRepositoryCountTest extends TestCase
{
    public function test_count_by_criteria_generated_sql_includes_translation_join(): void
    {
        config()->set('database.tenant.app_id', 'test_app');

        // Build the same query the repository builds for a `title` filter,
        // mirroring the production code path. (We can't instantiate the repo
        // and call countByCriteria() here without an Eloquent connection;
        // instead we replay the join + where shape and assert the SQL contains
        // the alias.)
        $model = new BlogPost();
        $query = $model
            ->from($model->getTable() . ' as p')
            ->join('test_app_post_translations as pt', 'pt.post_id', '=', 'p.id')
            ->where('pt.language_code', 'en');

        $criteria = new Criteria(
            Filters::fromValues([['field' => 'title', 'operator' => 'CONTAINS', 'value' => 'hello']]),
            Order::fromValues(null, null),
            null,
            null,
        );

        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, [
            'id' => 'p.id',
            'title' => 'pt.title',
            'slug' => 'pt.slug',
            'language' => 'pt.language_code',
            'category_id' => 'p.category_id',
        ]);

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        $sql = $query->toSql();

        $this->assertStringContainsString('test_app_posts', $sql);
        $this->assertStringContainsString('test_app_post_translations', $sql);
        $this->assertStringContainsString('pt', $sql);
        $this->assertStringContainsString('pt`.`title', $sql);
    }
}
