<?php

namespace Tomoki\SoftDeletesValue\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Tomoki\SoftDeletesValue\SoftDeletes;

class NullableNullDeletedWithNullUndeletedModel extends Model
{
    use SoftDeletes;

    protected $table = 'nullable_models';

    protected $fillable = ['deleted_at'];

    protected mixed $undeletedValue = null;

    protected bool $treatNullAsDeleted = true;
}
