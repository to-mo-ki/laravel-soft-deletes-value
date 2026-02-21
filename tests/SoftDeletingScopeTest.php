<?php

namespace Tomoki\SoftDeletesValue\Tests;

use Tomoki\SoftDeletesValue\Tests\Models\NullableModel;
use Tomoki\SoftDeletesValue\Tests\Models\NullableNullDeletedModel;
use Tomoki\SoftDeletesValue\Tests\Models\NullableNullDeletedWithNullUndeletedModel;
use Tomoki\SoftDeletesValue\Tests\Models\NullableNullUndeletedModel;
use Tomoki\SoftDeletesValue\Tests\Models\StrictModel;

class SoftDeletingScopeTest extends TestCase
{
    public function test_apply_adds_undeleted_value_constraint(): void
    {
        $active = StrictModel::create(['deleted_at' => '9999-12-31 23:59:59']);
        StrictModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $models = StrictModel::all();

        $this->assertCount(1, $models);
        $this->assertSame($active->id, $models->first()->id);
    }

    public function test_scope_excludes_null_by_default(): void
    {
        $active = NullableModel::create(['deleted_at' => '9999-12-31 23:59:59']);
        NullableModel::create(['deleted_at' => null]);
        NullableModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $models = NullableModel::all();

        $this->assertCount(1, $models);
        $this->assertSame($active->id, $models->first()->id);
    }

    public function test_restore_macro(): void
    {
        $trashed = StrictModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $affected = StrictModel::query()->restore();

        $this->assertEquals(1, $affected);
        $this->assertDatabaseHas('strict_models', [
            'id' => $trashed->id,
            'deleted_at' => '9999-12-31 23:59:59',
        ]);
    }

    public function test_only_trashed_macro(): void
    {
        StrictModel::create(['deleted_at' => '9999-12-31 23:59:59']);
        $trashed = StrictModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $models = StrictModel::onlyTrashed()->get();

        $this->assertCount(1, $models);
        $this->assertSame($trashed->id, $models->first()->id);
    }

    public function test_without_trashed_macro(): void
    {
        $active = NullableModel::create(['deleted_at' => '9999-12-31 23:59:59']);
        NullableModel::create(['deleted_at' => '2023-01-01 00:00:00']);
        NullableModel::create(['deleted_at' => null]);

        $models = NullableModel::withTrashed()->withoutTrashed()->get();

        $this->assertCount(1, $models);
        $this->assertSame($active->id, $models->first()->id);
    }

    public function test_with_trashed_macro(): void
    {
        StrictModel::create(['deleted_at' => '9999-12-31 23:59:59']);
        StrictModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $models = StrictModel::withTrashed()->get();

        $this->assertCount(2, $models);
    }

    public function test_only_trashed_uses_where_not_null_when_undeleted_value_is_null(): void
    {
        NullableNullUndeletedModel::create(['deleted_at' => null]);
        $trashed = NullableNullUndeletedModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $models = NullableNullUndeletedModel::onlyTrashed()->get();

        $this->assertCount(1, $models);
        $this->assertSame($trashed->id, $models->first()->id);
    }

    public function test_only_trashed_excludes_null_by_default(): void
    {
        NullableModel::create(['deleted_at' => '9999-12-31 23:59:59']);
        NullableModel::create(['deleted_at' => null]);
        $trashed = NullableModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $trashedByScope = NullableModel::onlyTrashed()->get();

        $this->assertCount(1, $trashedByScope);
        $this->assertSame($trashed->id, $trashedByScope->first()->id);
    }

    public function test_only_trashed_includes_null_when_enabled(): void
    {
        NullableNullDeletedModel::create(['deleted_at' => '9999-12-31 23:59:59']);
        $legacyNull = NullableNullDeletedModel::create(['deleted_at' => null]);
        $trashed = NullableNullDeletedModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $trashedByScope = NullableNullDeletedModel::onlyTrashed()->get();

        $this->assertCount(2, $trashedByScope);
        $this->assertEqualsCanonicalizing(
            [$legacyNull->id, $trashed->id],
            $trashedByScope->pluck('id')->all()
        );
    }

    public function test_only_trashed_does_not_expand_to_all_rows_when_undeleted_value_is_null(): void
    {
        NullableNullDeletedWithNullUndeletedModel::create(['deleted_at' => null]);
        $trashed = NullableNullDeletedWithNullUndeletedModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $models = NullableNullDeletedWithNullUndeletedModel::onlyTrashed()->get();

        $this->assertCount(1, $models);
        $this->assertSame($trashed->id, $models->first()->id);
    }
}
