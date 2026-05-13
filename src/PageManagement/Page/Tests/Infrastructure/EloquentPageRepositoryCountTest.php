<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Tests\Infrastructure;

use App\Models\Page;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentCriteriaConverter;
use Dba\DddSkeleton\Shared\Domain\Criteria\Criteria;
use Dba\DddSkeleton\Shared\Domain\Criteria\Filters;
use Dba\DddSkeleton\Shared\Domain\Criteria\Order;
use Tests\TestCase;

/**
 * Regression test for audit finding #37 — countByCriteria must JOIN the page
 * translations table so filters referencing the `pt.*` alias resolve at the
 * SQL layer. See the matching post-side test for context.
 */
final class EloquentPageRepositoryCountTest extends TestCase
{
    public function test_count_by_criteria_generated_sql_includes_translation_join(): void
    {
        config()->set('database.tenant.app_id', 'test_app');

        $model = new Page();
        $query = $model
            ->from($model->getTable() . ' as p')
            ->join('test_app_page_translations as pt', 'pt.page_id', '=', 'p.id')
            ->where('pt.language_code', 'en');

        $criteria = new Criteria(
            Filters::fromValues([['field' => 'title', 'operator' => 'CONTAINS', 'value' => 'hello']]),
            Order::fromValues(null, null),
            null,
            null,
        );

        $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, [
            'id'     => 'p.id',
            'status' => 'p.status',
            'slug'   => 'pt.slug',
            'title'  => 'pt.title',
        ]);

        $eloquentCriteria->each(static function ($method) use ($query) {
            call_user_func_array([$query, $method->name], $method->parameters);
        });

        $sql = $query->toSql();

        $this->assertStringContainsString('test_app_pages', $sql);
        $this->assertStringContainsString('test_app_page_translations', $sql);
        $this->assertStringContainsString('pt`.`title', $sql);
    }
}
