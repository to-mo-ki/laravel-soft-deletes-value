<?php

namespace Tomoki\SoftDeletesValue;

use Illuminate\Database\Eloquent\SoftDeletes as BaseSoftDeletes;

/**
 * @property string|null $deleted_at
 */
trait SoftDeletes
{
    /** @see \Illuminate\Database\Eloquent\SoftDeletes */
    use BaseSoftDeletes {
        initializeSoftDeletes as parentInitializeSoftDeletes;
    }

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes()
    {
        $this->parentInitializeSoftDeletes();

        $undeleted = $this->getUndeletedValue();

        if (! is_null($undeleted) && ! isset($this->attributes[$this->getDeletedAtColumn()])) {
            $this->attributes[$this->getDeletedAtColumn()] = $undeleted;
        }
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool
     */
    public function restore()
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = $this->getUndeletedValue();

        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        $deletedAt = $this->{$this->getDeletedAtColumn()};

        return ! is_null($deletedAt) && $deletedAt != $this->getUndeletedValue();
    }

    /**
     * Get the "undeleted" (active) value for the "deleted at" column.
     *
     * @return mixed
     */
    public function getUndeletedValue()
    {
        return $this->undeletedValue ?? null;
    }

    /**
     * Determine if null deleted_at values should be treated as deleted.
     */
    public function treatNullAsDeleted(): bool
    {
        if (! property_exists($this, 'treatNullAsDeleted')) {
            return false;
        }

        return $this->treatNullAsDeleted;
    }
}
