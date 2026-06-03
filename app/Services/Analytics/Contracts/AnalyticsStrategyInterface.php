<?php

namespace App\Services\Analytics\Contracts;

use Illuminate\Database\Query\Builder;

interface AnalyticsStrategyInterface
{
    /**
     * Execute the analytics query and return fully formatted data.
     *
     * @param Builder $query
     * @param string $tableName
     * @param array $filters
     * @return array
     */
    public function execute(Builder $query, string $tableName, array $filters): array;
}
