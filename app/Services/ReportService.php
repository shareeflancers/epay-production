<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\ActiveChallan;
use App\Models\ChallanHistory;
use App\Models\FeeFundCategory;
use App\Models\YearSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Determine if we should query the history table instead of active table based on filters.
     * Priority check is in active table, if empty, we fallback to history.
     *
     * @param array $filters
     * @return bool
     */
    protected function shouldQueryHistory(array $filters): bool
    {
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;

        // Force fallback to history if querying a month/year that is not the current running month/year
        if ($month && (int)$month !== (int)now()->month) {
            return true;
        }
        if ($year && (int)$year !== (int)now()->year) {
            return true;
        }

        $institutionId = $filters['institution_id'] ?? null;
        $classId = $filters['school_class_id'] ?? null;
        $section = $filters['section'] ?? null;
        $feeFundCategoryId = $filters['fee_fund_category_id'] ?? null;
        $yearSession = $filters['year_session'] ?? null;

        $query = ActiveChallan::query();

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        if ($classId) {
            $query->where('school_class_id', $classId);
        }
        if ($feeFundCategoryId) {
            $query->where('fee_fund_category_id', $feeFundCategoryId);
        }
        if ($month) {
            $query->whereMonth('due_date', $month);
        }
        if ($year) {
            $query->whereYear('due_date', $year);
        }
        if ($yearSession) {
            $query->whereHas('yearSession', function ($q) use ($yearSession) {
                $q->where('name', $yearSession);
            });
        }
        if ($section) {
            $query->whereHas('consumer.profileDetails', function ($q) use ($section) {
                $q->where('section', $section);
            });
        }

        return !$query->exists();
    }

    /**
     * Get summary totals and paginated institutions with counts.
     *
     * @param array $filters
     * @return array
     */
    public function getSummaryAndPaginatedInstitutions(array $filters): array
    {
        $institutionId = $filters['institution_id'] ?? null;
        $classId = $filters['school_class_id'] ?? null;
        $section = $filters['section'] ?? null;
        $feeFundCategoryId = $filters['fee_fund_category_id'] ?? null;
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;
        $yearSession = $filters['year_session'] ?? null;

        $useHistory = $this->shouldQueryHistory($filters);
        $relationName = $useHistory ? 'challanHistories' : 'activeChallans';

        $query = Institution::where('is_active', true)
            ->where('is_deleted', false);

        if ($institutionId) {
            $query->where('id', $institutionId);
        }

        // Only include institutions that have matching challans (non-zero total)
        $query->whereHas($relationName, function ($q) use ($classId, $section, $feeFundCategoryId, $month, $year, $yearSession) {
            if ($classId) $q->where('school_class_id', $classId);
            if ($feeFundCategoryId) $q->where('fee_fund_category_id', $feeFundCategoryId);
            if ($month) $q->whereMonth('due_date', $month);
            if ($year) $q->whereYear('due_date', $year);
            if ($yearSession) {
                $q->whereHas('yearSession', function ($sq) use ($yearSession) {
                    $sq->where('name', $yearSession);
                });
            }
            if ($section) {
                $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                    $pq->where('section', $section);
                });
            }
        });

        // Calculate totals for all matched institutions (before pagination)
        $totalsQuery = clone $query;
        $totals = $totalsQuery->withCount([
            "$relationName as total" => function ($q) use ($classId, $section, $feeFundCategoryId, $month, $year, $yearSession) {
                if ($classId) $q->where('school_class_id', $classId);
                if ($feeFundCategoryId) $q->where('fee_fund_category_id', $feeFundCategoryId);
                if ($month) $q->whereMonth('due_date', $month);
                if ($year) $q->whereYear('due_date', $year);
                if ($yearSession) {
                    $q->whereHas('yearSession', function ($sq) use ($yearSession) {
                        $sq->where('name', $yearSession);
                    });
                }
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
            "$relationName as paid" => function ($q) use ($classId, $section, $feeFundCategoryId, $month, $year, $yearSession) {
                $q->where('status', 'P');
                if ($classId) $q->where('school_class_id', $classId);
                if ($feeFundCategoryId) $q->where('fee_fund_category_id', $feeFundCategoryId);
                if ($month) $q->whereMonth('due_date', $month);
                if ($year) $q->whereYear('due_date', $year);
                if ($yearSession) {
                    $q->whereHas('yearSession', function ($sq) use ($yearSession) {
                        $sq->where('name', $yearSession);
                    });
                }
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
            "$relationName as synced" => function ($q) use ($classId, $section, $feeFundCategoryId, $month, $year, $yearSession) {
                $q->where('sms_sync', 1);
                if ($classId) $q->where('school_class_id', $classId);
                if ($feeFundCategoryId) $q->where('fee_fund_category_id', $feeFundCategoryId);
                if ($month) $q->whereMonth('due_date', $month);
                if ($year) $q->whereYear('due_date', $year);
                if ($yearSession) {
                    $q->whereHas('yearSession', function ($sq) use ($yearSession) {
                        $sq->where('name', $yearSession);
                    });
                }
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            }
        ])->get();

        $summaryTotals = [
            'total' => $totals->sum('total'),
            'paid' => $totals->sum('paid'),
            'synced' => $totals->sum('synced'),
        ];

        $institutions = $query->withCount([
            "$relationName as total_count" => function ($q) use ($classId, $section, $feeFundCategoryId, $month, $year, $yearSession) {
                if ($classId) $q->where('school_class_id', $classId);
                if ($feeFundCategoryId) $q->where('fee_fund_category_id', $feeFundCategoryId);
                if ($month) $q->whereMonth('due_date', $month);
                if ($year) $q->whereYear('due_date', $year);
                if ($yearSession) {
                    $q->whereHas('yearSession', function ($sq) use ($yearSession) {
                        $sq->where('name', $yearSession);
                    });
                }
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
            "$relationName as paid_count" => function ($q) use ($classId, $section, $feeFundCategoryId, $month, $year, $yearSession) {
                $q->where('status', 'P');
                if ($classId) $q->where('school_class_id', $classId);
                if ($feeFundCategoryId) $q->where('fee_fund_category_id', $feeFundCategoryId);
                if ($month) $q->whereMonth('due_date', $month);
                if ($year) $q->whereYear('due_date', $year);
                if ($yearSession) {
                    $q->whereHas('yearSession', function ($sq) use ($yearSession) {
                        $sq->where('name', $yearSession);
                    });
                }
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
            "$relationName as unpaid_count" => function ($q) use ($classId, $section, $feeFundCategoryId, $month, $year, $yearSession) {
                $q->where('status', 'U');
                if ($classId) $q->where('school_class_id', $classId);
                if ($feeFundCategoryId) $q->where('fee_fund_category_id', $feeFundCategoryId);
                if ($month) $q->whereMonth('due_date', $month);
                if ($year) $q->whereYear('due_date', $year);
                if ($yearSession) {
                    $q->whereHas('yearSession', function ($sq) use ($yearSession) {
                        $sq->where('name', $yearSession);
                    });
                }
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
            "$relationName as synced_count" => function ($q) use ($classId, $section, $feeFundCategoryId, $month, $year, $yearSession) {
                $q->where('sms_sync', 1);
                if ($classId) $q->where('school_class_id', $classId);
                if ($feeFundCategoryId) $q->where('fee_fund_category_id', $feeFundCategoryId);
                if ($month) $q->whereMonth('due_date', $month);
                if ($year) $q->whereYear('due_date', $year);
                if ($yearSession) {
                    $q->whereHas('yearSession', function ($sq) use ($yearSession) {
                        $sq->where('name', $yearSession);
                    });
                }
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
        ])
        ->orderBy('display_order')
        ->orderBy('name')
        ->paginate(10)
        ->withQueryString();

        return [
            'summaryTotals' => $summaryTotals,
            'institutions' => $institutions,
        ];
    }

    /**
     * Get class and section wise stats for a specific institution.
     *
     * @param int $institutionId
     * @param array $filters
     * @return Collection
     */
    public function getInstitutionStats(int $institutionId, array $filters): Collection
    {
        $classId = $filters['school_class_id'] ?? null;
        $section = $filters['section'] ?? null;
        $feeFundCategoryId = $filters['fee_fund_category_id'] ?? null;
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;
        $yearSession = $filters['year_session'] ?? null;

        $useHistory = $this->shouldQueryHistory($filters);
        $tableName = $useHistory ? 'challan_history' : 'active_challans';

        $query = DB::table($tableName . ' as ac')
            ->join('consumers as c', 'ac.consumer_id', '=', 'c.id')
            ->join('profile_details as pd', 'c.id', '=', 'pd.consumer_id')
            ->leftJoin('school_classes as sc', 'ac.school_class_id', '=', 'sc.id')
            ->where('ac.institution_id', $institutionId)
            ->where('pd.is_active', true);

        if ($classId) {
            $query->where('ac.school_class_id', $classId);
        }
        if ($section) {
            $query->where('pd.section', $section);
        }
        if ($feeFundCategoryId) {
            $query->where('ac.fee_fund_category_id', $feeFundCategoryId);
        }
        if ($month) {
            $query->whereMonth('ac.due_date', $month);
        }
        if ($year) {
            $query->whereYear('ac.due_date', $year);
        }
        if ($yearSession) {
            $query->join('year_sessions as ys', 'ac.year_session_id', '=', 'ys.id')
                  ->where('ys.name', $yearSession);
        }

        return $query->select([
                'ac.school_class_id',
                'sc.name as class_name',
                'pd.section',
                DB::raw('count(ac.id) as total_count'),
                DB::raw('sum(case when ac.status = "P" then 1 else 0 end) as paid_count'),
                DB::raw('sum(case when ac.status = "U" then 1 else 0 end) as unpaid_count'),
                DB::raw('sum(case when ac.sms_sync = 1 then 1 else 0 end) as synced_count'),
            ])
            ->groupBy('ac.school_class_id', 'sc.name', 'pd.section')
            ->orderBy('sc.name')
            ->orderBy('pd.section')
            ->get();
    }

    /**
     * Get paginated challans/students for an institution.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getStudentsList(array $filters): LengthAwarePaginator
    {
        $institutionId = $filters['institution_id'] ?? null;
        $classId = $filters['school_class_id'] ?? null;
        $section = $filters['section'] ?? null;
        $feeFundCategoryId = $filters['fee_fund_category_id'] ?? null;
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;
        $yearSession = $filters['year_session'] ?? null;

        $useHistory = $this->shouldQueryHistory($filters);
        $modelClass = $useHistory ? ChallanHistory::class : ActiveChallan::class;

        $query = $modelClass::with(['consumer.profileDetails', 'schoolClass'])
            ->where('institution_id', $institutionId);

        if ($classId) {
            $query->where('school_class_id', $classId);
        }

        if ($feeFundCategoryId) {
            $query->where('fee_fund_category_id', $feeFundCategoryId);
        }

        if ($month) {
            $query->whereMonth('due_date', $month);
        }

        if ($year) {
            $query->whereYear('due_date', $year);
        }

        if ($yearSession) {
            $query->whereHas('yearSession', function ($q) use ($yearSession) {
                $q->where('name', $yearSession);
            });
        }

        if ($section) {
            $query->whereHas('consumer.profileDetails', function ($q) use ($section) {
                $q->where('section', $section);
            });
        }

        return $query->latest()->paginate(50)->withQueryString();
    }

    /**
     * Get filter options list.
     *
     * @return array
     */
    public function getReportFilterOptions(): array
    {
        return [
            'institutions' => Institution::where('is_active', true)->select('id', 'name as label')->get(),
            'classes' => SchoolClass::where('is_active', true)->select('id', 'name as label')->get(),
            'feeFundCategories' => FeeFundCategory::where('is_active', true)->select('id', 'category_title as label')->get(),
            'yearSessions' => YearSession::where('is_active', true)
                ->select('name as id', 'name as label')
                ->distinct()
                ->orderBy('name')
                ->get(),
        ];
    }
}
