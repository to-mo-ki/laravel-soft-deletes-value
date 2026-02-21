<?php

namespace Tomoki\SoftDeletesValue\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Tomoki\SoftDeletesValue\SoftDeletes;

/**
 * @property string|null $deleted_at
 */
class NullableNullDeletedModel extends Model
{
    use SoftDeletes;

    protected $table = 'nullable_models';

    protected $fillable = ['deleted_at'];

    protected mixed $undeletedValue = '9999-12-31 23:59:59';

    protected bool $treatNullAsDeleted = true;
}
