<?php

use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
Route::get('/analytics/revenue', [AnalyticsController::class, 'revenue']);
Route::get('/analytics/orders', [AnalyticsController::class, 'orders']);
Route::get('/analytics/products', [AnalyticsController::class, 'products']);
