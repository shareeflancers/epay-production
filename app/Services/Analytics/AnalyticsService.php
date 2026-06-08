<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use App\Models\ActiveChallan;
use App\Models\Region;
use App\Models\Institution;
use App\Services\Analytics\Strategies\InstitutionStrategy;
use App\Services\Analytics\Strategies\RegionStrategy;
use App\Services\Analytics\Strategies\ClassSectionStrategy;
use App\Services\Analytics\Strategies\InstitutionCategoryStrategy;
use App\Services\Analytics\Strategies\OverallStrategy;
use App\Services\Analytics\Strategies\DetailedFundheadStrategy;
use App\Services\Analytics\Traits\AnalyticsHistoryTrait;
use App\Services\FeeCategoryService;

class AnalyticsService
{
    use AnalyticsHistoryTrait;

    protected FeeCategoryService $feeCategoryService;

    public function __construct(FeeCategoryService $feeCategoryService)
    {
        $this->feeCategoryService = $feeCategoryService;
    }

    /**
     * Get analytics based on filters.
     *
     * @param string $type
     * @param array $filters
     * @return array
     */
    public function getAnalytics(string $type, array $filters): array
    {
        $useHistory = $this->shouldQueryHistory($filters);
        $tableName = $useHistory ? 'challan_history' : 'active_challans';

        $query = DB::table($tableName);

        $strategy = $this->resolveStrategy($type, $filters);

        // For standard strategies, we need to apply base filters
        if (!($strategy instanceof DetailedFundheadStrategy)) {
            $this->applyBaseFilters($query, $tableName, $filters);
        }

        $results = $strategy->execute($query, $tableName, $filters);

        // Map root data based on filters
        $payload = [];

        if (isset($filters['institution_id'])) {
            $payload['institution_id'] = (int) $filters['institution_id'];
            $institution = Institution::find($filters['institution_id']);
            if ($institution) {
                $payload['institution_name'] = $institution->name;
            }
        }
        if (isset($filters['region_id'])) {
            $payload['region_id'] = (int) $filters['region_id'];
            $region = Region::find($filters['region_id']);
            if ($region) {
                $payload['region_name'] = $region->name;
            }
        }
        if (isset($filters['month'])) {
            $payload['month'] = (int) $filters['month'];
        }
        if (isset($filters['year'])) {
            $payload['year'] = (int) $filters['year'];
        }
        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $payload['from_date'] = $filters['from_date'];
            $payload['to_date'] = $filters['to_date'];
        }
        if (isset($filters['year_session'])) {
            $payload['year_session'] = $filters['year_session'];
        }
        if (!empty($filters['detailed'])) {
            $payload['detailed'] = 1;
        }

        $payload['data'] = $results;

        return $payload;
    }

    protected function resolveStrategy(string $type, array $filters)
    {
        if (!empty($filters['detailed'])) {
            return new DetailedFundheadStrategy($this->feeCategoryService, $type);
        }

        switch ($type) {
            case 'institution':
                return new InstitutionStrategy();
            case 'region':
                return new RegionStrategy();
            case 'class_section':
                return new ClassSectionStrategy();
            case 'institution_category':
                return new InstitutionCategoryStrategy();
            default:
                return new OverallStrategy();
        }
    }

    protected function applyBaseFilters($query, string $tableName, array $filters): void
    {
        $institutionId = $filters['institution_id'] ?? null;
        $regionId = $filters['region_id'] ?? null;
        $classId = $filters['school_class_id'] ?? null;
        $section = $filters['section'] ?? null;
        $fee_fund_category_id = $filters['fee_fund_category_id'] ?? null;
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $yearSession = $filters['year_session'] ?? null;

        $query->leftJoin('fee_fund_category', $tableName . '.fee_fund_category_id', '=', 'fee_fund_category.id')
              ->leftJoin('consumers', $tableName . '.consumer_id', '=', 'consumers.id');

        if ($institutionId) {
            $query->where($tableName . '.institution_id', $institutionId);
        }
        if ($classId) {
            $query->where($tableName . '.school_class_id', $classId);
        }
        if ($regionId) {
            $query->where($tableName . '.region_id', $regionId);
        }
        if ($fee_fund_category_id) {
            $query->where($tableName . '.fee_fund_category_id', $fee_fund_category_id);
        }
        if ($month) {
            $query->whereMonth($tableName . '.due_date', $month);
        }
        if ($year) {
            $query->whereYear($tableName . '.due_date', $year);
        }
        if ($fromDate && $toDate) {
            try {
                $start = \Carbon\Carbon::parse($fromDate)->startOfMonth()->format('Y-m-d');
                $end = \Carbon\Carbon::parse($toDate)->endOfMonth()->format('Y-m-d');
                $query->whereBetween($tableName . '.due_date', [$start, $end]);
            } catch (\Exception $e) {
                // fallback if unparseable
            }
        }
        if ($yearSession) {
            $query->join('year_sessions', $tableName . '.year_session_id', '=', 'year_sessions.id')
                  ->where('year_sessions.name', $yearSession);
        }
        if ($section) {
            $query->where($tableName . '.section', $section);
        }
    }
}
