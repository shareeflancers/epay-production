<?php

use App\Http\Controllers\Admin\FeeFundCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UtilitiesController;
use App\Http\Controllers\Admin\ConsumersController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\LevelController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// Admin routes (authentication can be added later)
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    // Fee Fund Category CRUD
    Route::post('/fee-fund-categories/reorder', [FeeFundCategoryController::class, 'reorder'])->name('admin.fee-fund-categories.reorder');
    Route::get('/fee-fund-categories', [FeeFundCategoryController::class, 'index'])->name('admin.fee-fund-categories.index');
    Route::post('/fee-fund-categories', [FeeFundCategoryController::class, 'store'])->name('admin.fee-fund-categories.store');
    Route::put('/fee-fund-categories/{feeFundCategory}', [FeeFundCategoryController::class, 'update'])->name('admin.fee-fund-categories.update');
    Route::delete('/fee-fund-categories/{feeFundCategory}', [FeeFundCategoryController::class, 'destroy'])->name('admin.fee-fund-categories.destroy');
    Route::put('/fee-fund-categories/{feeFundCategory}/status', [FeeFundCategoryController::class, 'updateStatus'])->name('admin.fee-fund-categories.update-status');

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
    Route::post('/utilities/generateChallans', [SettingsController::class, 'generateBulkChallans'])->name('admin.utilities.generateChallans');

    // 1Link Testing
    Route::get('/one-link-testing', function () {
        return Inertia::render('Admin/OneLinkTesting');
    })->name('admin.one-link-testing');
});
