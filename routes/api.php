<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChallansController;
use App\Http\Controllers\ApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/bill-inquiry', [ChallansController::class, 'billInquiry']);
Route::post('/bill-payment', [ChallansController::class, 'billPayment']);
Route::get('/fee-categories', [ApiController::class, 'fetchFeeCategories']);
