<?php

use App\Http\Controllers\Admin\FeeFundCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UtilitiesController;
use App\Http\Controllers\Admin\ConsumersController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\FeeFundHeadController;
use App\Http\Controllers\Admin\YearSessionsController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/challan/search', [\App\Http\Controllers\PublicChallanController::class, 'search'])->name('challan.search');
Route::get('/challan/view/{challan_no}', [\App\Http\Controllers\PublicChallanController::class, 'show'])->name('challan.view');
Route::get('/challan/verify/{consumer_number}', [\App\Http\Controllers\PublicChallanController::class, 'verify'])->name('challan.verify');
Route::get('/challans/bulk-print', [\App\Http\Controllers\PublicChallanController::class, 'bulkShow'])->name('challans.bulk-print');

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin routes (Protected by auth middleware)
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    // Fee Fund Category CRUD
    Route::post('/fee-fund-categories/reorder', [FeeFundCategoryController::class, 'reorder'])->name('admin.fee-fund-categories.reorder');
    Route::get('/fee-fund-categories', [FeeFundCategoryController::class, 'index'])->name('admin.fee-fund-categories.index');
    Route::post('/fee-fund-categories', [FeeFundCategoryController::class, 'store'])->name('admin.fee-fund-categories.store');
    Route::put('/fee-fund-categories/{feeFundCategory}', [FeeFundCategoryController::class, 'update'])->name('admin.fee-fund-categories.update');
    Route::delete('/fee-fund-categories/{feeFundCategory}', [FeeFundCategoryController::class, 'destroy'])->name('admin.fee-fund-categories.destroy');
    Route::put('/fee-fund-categories/{feeFundCategory}/status', [FeeFundCategoryController::class, 'updateStatus'])->name('admin.fee-fund-categories.update-status');

    // Fee Fund Heads CRUD
    Route::post('/fee-fund-heads/reorder', [FeeFundHeadController::class, 'reorder'])->name('admin.fee-fund-heads.reorder');
    Route::get('/fee-fund-heads', [FeeFundHeadController::class, 'index'])->name('admin.fee-fund-heads.index');
    Route::post('/fee-fund-heads', [FeeFundHeadController::class, 'store'])->name('admin.fee-fund-heads.store');
    Route::put('/fee-fund-heads/{feeFundHead}', [FeeFundHeadController::class, 'update'])->name('admin.fee-fund-heads.update');
    Route::delete('/fee-fund-heads/{feeFundHead}', [FeeFundHeadController::class, 'destroy'])->name('admin.fee-fund-heads.destroy');
    Route::put('/fee-fund-heads/{feeFundHead}/status', [FeeFundHeadController::class, 'updateStatus'])->name('admin.fee-fund-heads.update-status');

    // Fee Structure CRUD
    Route::post('/fee-structure/reorder', [FeeStructureController::class, 'reorder'])->name('admin.fee-structure.reorder');
    Route::get('/fee-structure', [FeeStructureController::class, 'index'])->name('admin.fee-structure.index');
    Route::post('/fee-structure', [FeeStructureController::class, 'store'])->name('admin.fee-structure.store');
    Route::put('/fee-structure/{feeStructure}', [FeeStructureController::class, 'update'])->name('admin.fee-structure.update');
    Route::delete('/fee-structure/{feeStructure}', [FeeStructureController::class, 'destroy'])->name('admin.fee-structure.destroy');
    Route::put('/fee-structure/{feeStructure}/status', [FeeStructureController::class, 'updateStatus'])->name('admin.fee-structure.update-status');

    // Regions CRUD
    Route::post('/regions/reorder', [RegionController::class, 'reorder'])->name('admin.regions.reorder');
    Route::get('/regions', [RegionController::class, 'index'])->name('admin.regions.index');
    Route::post('/regions', [RegionController::class, 'store'])->name('admin.regions.store');
    Route::put('/regions/{region}', [RegionController::class, 'update'])->name('admin.regions.update');
    Route::delete('/regions/{region}', [RegionController::class, 'destroy'])->name('admin.regions.destroy');
    Route::put('/regions/{region}/status', [RegionController::class, 'updateStatus'])->name('admin.regions.update-status');

    // Levels CRUD
    Route::post('/levels/reorder', [LevelController::class, 'reorder'])->name('admin.levels.reorder');
    Route::get('/levels', [LevelController::class, 'index'])->name('admin.levels.index');
    Route::post('/levels', [LevelController::class, 'store'])->name('admin.levels.store');
    Route::put('/levels/{level}', [LevelController::class, 'update'])->name('admin.levels.update');
    Route::delete('/levels/{level}', [LevelController::class, 'destroy'])->name('admin.levels.destroy');
    Route::put('/levels/{level}/status', [LevelController::class, 'updateStatus'])->name('admin.levels.update-status');

    // Classes Routes
    Route::post('/classes/reorder', [ClassController::class, 'reorder'])->name('admin.classes.reorder');
    Route::get('/classes', [ClassController::class, 'index'])->name('admin.classes.index');
    Route::post('/classes', [ClassController::class, 'store'])->name('admin.classes.store');
    Route::put('/classes/{class}', [ClassController::class, 'update'])->name('admin.classes.update');
    Route::delete('/classes/{class}', [ClassController::class, 'destroy'])->name('admin.classes.destroy');
    Route::put('/classes/{class}/status', [ClassController::class, 'updateStatus'])->name('admin.classes.update-status');

    // Year Sessions CRUD
    Route::get('/year-sessions', [YearSessionsController::class, 'index'])->name('admin.year-sessions.index');
    Route::post('/year-sessions', [YearSessionsController::class, 'store'])->name('admin.year-sessions.store');
    Route::put('/year-sessions/{yearSession}', [YearSessionsController::class, 'update'])->name('admin.year-sessions.update');
    Route::delete('/year-sessions/{yearSession}', [YearSessionsController::class, 'destroy'])->name('admin.year-sessions.destroy');
    Route::put('/year-sessions/{yearSession}/status', [YearSessionsController::class, 'updateStatus'])->name('admin.year-sessions.update-status');

    // Consumers CRUD
    Route::get('/consumers/{type}', [ConsumersController::class, 'index'])->name('admin.consumers.index');
    Route::put('/consumers/{consumer}', [ConsumersController::class, 'update'])->name('admin.consumers.update');
    Route::delete('/consumers/{consumer}', [ConsumersController::class, 'destroy'])->name('admin.consumers.destroy');
    Route::put('/consumers/{consumer}/status', [ConsumersController::class, 'updateStatus'])->name('admin.consumers.update-status');

    // Institutions CRUD
    Route::post('/institutions/reorder', [\App\Http\Controllers\Admin\InstitutionController::class, 'reorder'])->name('admin.institutions.reorder');
    Route::get('/institutions', [\App\Http\Controllers\Admin\InstitutionController::class, 'index'])->name('admin.institutions.index');
    Route::post('/institutions', [\App\Http\Controllers\Admin\InstitutionController::class, 'store'])->name('admin.institutions.store');
    Route::put('/institutions/{institution}', [\App\Http\Controllers\Admin\InstitutionController::class, 'update'])->name('admin.institutions.update');
    Route::delete('/institutions/{institution}', [\App\Http\Controllers\Admin\InstitutionController::class, 'destroy'])->name('admin.institutions.destroy');
    Route::put('/institutions/{institution}/status', [\App\Http\Controllers\Admin\InstitutionController::class, 'updateStatus'])->name('admin.institutions.update-status');

    // APIs
    Route::get('/api/fetch/{type}', [UtilitiesController::class, 'apiFetch'])->name('admin.api.fetch');

    // Settings / Category Bind
    Route::get('/utilities/categoryBind', [SettingsController::class, 'index'])->name('admin.settings.categoryBind');
    Route::post('/settings/search', [SettingsController::class, 'search'])->name('admin.settings.search');
    Route::get('/settings/categories', [SettingsController::class, 'getCategories'])->name('admin.settings.categories');
    Route::put('/settings/student/{id}', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::put('/settings/student/{id}/status', [SettingsController::class, 'toggleStatus'])->name('admin.settings.toggle-status');
    Route::delete('/settings/student/{id}', [SettingsController::class, 'destroy'])->name('admin.settings.destroy');

    // Challan Update & Bulk Generation
    Route::get('/utilities/challanUpdate', [SettingsController::class, 'challanIndex'])->name('admin.utilities.challanUpdate');
    Route::post('/settings/challan/search', [SettingsController::class, 'challanSearch'])->name('admin.settings.challan.search');
    Route::put('/settings/challan/{id}', [SettingsController::class, 'challanUpdateSingle'])->name('admin.settings.challan.update');
    Route::get('/settings/year-sessions', [SettingsController::class, 'getYearSessions'])->name('admin.settings.year-sessions');
    Route::get('/settings/challan-metadata', [SettingsController::class, 'getChallanMetadata'])->name('admin.settings.challan-metadata');
    Route::get('/settings/challan-history', [SettingsController::class, 'challanHistoryIndex'])->name('admin.settings.challan-history');
    Route::post('/settings/retry-sms-sync', [SettingsController::class, 'retrySmsSync'])->name('admin.settings.retry-sms-sync');
    Route::post('/utilities/generateChallans', [SettingsController::class, 'generateBulkChallans'])->name('admin.utilities.generateChallans');
    Route::post('/utilities/archiveChallans', [SettingsController::class, 'moveToHistory'])->name('admin.utilities.archiveChallans');

    // API & External Systems Testing
    Route::get('/api-testing', function () {
        return Inertia::render('Admin/ApiTesting');
    })->name('admin.api-testing');

    Route::get('/monthly-procedure', function () {
        return Inertia::render('Admin/MonthlyProcedure');
    })->name('admin.monthly-procedure');

    // Security Audit
    Route::get('/security-audit', [\App\Http\Controllers\Admin\SecurityController::class, 'index'])->name('admin.security-audit');
    Route::get('/security-audit/audit-logs', [\App\Http\Controllers\Admin\SecurityController::class, 'getAuditLogs']);
    Route::get('/security-audit/api-logs', [\App\Http\Controllers\Admin\SecurityController::class, 'getApiLogs']);
    Route::get('/security-audit/latest-snapshots', [\App\Http\Controllers\Admin\SecurityController::class, 'getLatestSnapshots']);
    Route::get('/security-audit/snapshot-history', [\App\Http\Controllers\Admin\SecurityController::class, 'getSnapshotHistory']);

    // Procedure Rollback
    Route::post('/procedure/rollback/{id}', function ($id) {
        set_time_limit(300); // Increase to 5 minutes
        ini_set('memory_limit', '512M');
        
        try {
            \App\Services\ProcedureService::rollback($id);
            return response()->json(['success' => true, 'message' => 'Procedure rolled back successfully.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Rollback failed for snapshot ' . $id . ': ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Rollback failed: ' . $e->getMessage()
            ], 500);
        }
    })->name('admin.procedure.rollback');


    // 1Link Testing
    Route::get('/one-link-testing', function () {
        return Inertia::render('Admin/OneLinkTesting');
    })->name('admin.one-link-testing');

    // Analytical Reports
    Route::get('/reports/analytical', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('admin.reports.analytical');
});
