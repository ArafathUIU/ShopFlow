<?php

use App\Http\Controllers\Api\V1\DocumentationController;
use Illuminate\Support\Facades\Route;

Route::get('/docs/openapi.json', [DocumentationController::class, 'openapi'])->name('docs.openapi');
Route::view('/docs', 'docs.openapi')->name('docs');
