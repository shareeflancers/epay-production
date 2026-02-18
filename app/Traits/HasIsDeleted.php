<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasIsDeleted
{
    /**
     * Boot the trait.
     */
    protected static function bootHasIsDeleted()
    {
        static::addGlobalScope('is_deleted', function (Builder $builder) {
            $builder->where($builder->getModel()->qualifyColumn('is_deleted'), 0);
        });
    }

    /**
     * initialize soft deleted
     */
    public function initializeHasIsDeleted()
    {
        $this->fillable[] = 'is_deleted';
        $this->casts['is_deleted'] = 'boolean';
    }

    /**
     * Perform a soft delete.
     */
    public function softDelete()
    {
        $this->is_deleted = 1;
        $this->save();
    }

    /**
     * Restore a soft-deleted model.
     */
    public function restore()
    {
        $this->is_deleted = 0;
        $this->save();
    }

    /**
     * Include soft-deleted models in the query.
     */
    public static function withDeleted()
    {
        return static::withoutGlobalScope('is_deleted');
    }
}
