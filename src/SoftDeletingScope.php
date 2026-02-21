<?php

namespace Tomoki\SoftDeletesValue;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope as BaseSoftDeletingScope;

class SoftDeletingScope extends BaseSoftDeletingScope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getQualifiedDeletedAtColumn(), $model->getUndeletedValue());
    }

    /**
     * Add the restore extension to the builder.
     *
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            $model = $builder->getModel();

            return $builder->update([$model->getDeletedAtColumn() => $model->getUndeletedValue()]);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * @return void
     */
    protected function addWithoutTrashed(Builder $builder)
    {
        /** @var \Tomoki\SoftDeletesValue\SoftDeletingScope $scope */
        $scope = $this;

        $builder->macro('withoutTrashed', function (Builder $builder) use ($scope) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($scope)->where(
                $model->getQualifiedDeletedAtColumn(),
                $model->getUndeletedValue()
            );

            return $builder;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        /** @var \Tomoki\SoftDeletesValue\SoftDeletingScope $scope */
        $scope = $this;

        $builder->macro('onlyTrashed', function (Builder $builder) use ($scope) {
            $model = $builder->getModel();
            $column = $model->getQualifiedDeletedAtColumn();
            $undeletedValue = $model->getUndeletedValue();

            $builder->withoutGlobalScope($scope)
                ->where(function (Builder $builder) use ($model, $column, $undeletedValue) {
                    $builder->where($column, '!=', $undeletedValue);

                    if ($model->treatNullAsDeleted() && ! is_null($undeletedValue)) {
                        $builder->orWhereNull($column);
                    }
                });

            return $builder;
        });
    }
}
