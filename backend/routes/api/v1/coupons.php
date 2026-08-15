<?php

use App\Http\Controllers\Api\V1\Coupons\CouponController;
use Illuminate\Support\Facades\Route;

Route::post('coupons/validate', [CouponController::class, 'validate'])
    ->middleware('throttle:30,1')
    ->name('coupons.validate');
