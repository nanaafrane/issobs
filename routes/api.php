<?php

use App\Http\Controllers\SendEmployeeMoneyController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('/sendEmployeeMoney', SendEmployeeMoneyController::class)->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/webhook-endpoint', [SendEmployeeMoneyController::class, 'webHook']);
