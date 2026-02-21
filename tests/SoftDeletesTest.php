<?php

namespace Tomoki\SoftDeletesValue\Tests;

use Tomoki\SoftDeletesValue\SoftDeletingScope;
use Tomoki\SoftDeletesValue\Tests\Models\NullableModel;
use Tomoki\SoftDeletesValue\Tests\Models\NullableNullDeletedModel;
use Tomoki\SoftDeletesValue\Tests\Models\NullableNullUndeletedModel;
use Tomoki\SoftDeletesValue\Tests\Models\StrictModel;

class SoftDeletesTest extends TestCase
{
    public function test_model_initialization_with_custom_value(): void
    {
        $model = new StrictModel;
        $this->assertEquals('9999-12-31 23:59:59', $model->deleted_at);
        $this->assertEquals('datetime', $model->getCasts()['deleted_at']);

        $defaultModel = new NullableNullUndeletedModel;
        $this->assertArrayNotHasKey('deleted_at', $defaultModel->getAttributes());

        $existingModel = new StrictModel(['deleted_at' => '2025-01-01 00:00:00']);
        $this->assertEquals('2025-01-01 00:00:00', $existingModel->deleted_at);
    }

    public function test_model_restore_sets_custom_value_and_fires_events(): void
    {
        $model = StrictModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        $restoringFired = false;
        $restoredFired = false;
        StrictModel::restoring(function () use (&$restoringFired) {
            $restoringFired = true;
        });

        StrictModel::restored(function ($model) use (&$restoredFired) {
            $restoredFired = true;
        });

        $result = $model->restore();

        $this->assertTrue($result);
        $this->assertEquals('9999-12-31 23:59:59', $model->deleted_at);
        $this->assertTrue($model->exists);
        $this->assertTrue($restoringFired);
        $this->assertTrue($restoredFired);

        $this->assertDatabaseHas('strict_models', [
            'id' => $model->id,
            'deleted_at' => '9999-12-31 23:59:59',
        ]);
    }

    public function test_restore_bails_if_restoring_event_returns_false(): void
    {
        $model = StrictModel::create(['deleted_at' => '2023-01-01 00:00:00']);

        StrictModel::restoring(function () {
            return false;
        });

        $this->assertFalse($model->restore());
        $this->assertEquals('2023-01-01 00:00:00', $model->deleted_at);

        $this->assertDatabaseHas('strict_models', [
            'id' => $model->id,
            'deleted_at' => '2023-01-01 00:00:00',
        ]);
    }

    public function test_trashed_returns_false_for_undeleted_value(): void
    {
        $model = new StrictModel;

        $model->setRawAttributes(['deleted_at' => '9999-12-31 23:59:59']);
        $this->assertFalse($model->trashed());
    }

    public function test_trashed_returns_true_for_deleted_value(): void
    {
        $model = new StrictModel;

        $model->setRawAttributes(['deleted_at' => '2023-01-01 00:00:00']);
        $this->assertTrue($model->trashed());
    }

    public function test_trashed_returns_false_for_null_value(): void
    {
        $model = new StrictModel;

        $model->setRawAttributes(['deleted_at' => null]);
        $this->assertFalse($model->trashed());
    }

    public function test_boot_soft_deletes_registers_global_scope(): void
    {
        $model = new StrictModel;
        $this->assertArrayHasKey(SoftDeletingScope::class, $model->getGlobalScopes());
    }

    public function test_default_undeleted_value_is_null(): void
    {
        $model = new NullableNullUndeletedModel;
        $this->assertNull($model->getUndeletedValue());
    }

    public function test_treat_null_as_deleted_defaults_to_false(): void
    {
        $model = new NullableModel;
        $this->assertFalse($model->treatNullAsDeleted());
    }

    public function test_treat_null_as_deleted_can_be_enabled(): void
    {
        $model = new NullableNullDeletedModel;
        $this->assertTrue($model->treatNullAsDeleted());
    }
}
