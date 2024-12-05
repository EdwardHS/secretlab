<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\KeyValues;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['middleware' => ['throttle:api'],'prefix' => 'object'], function() {
    Route::post('/', [KeyValues::class, 'store']);
    Route::get('/get_all_records', [KeyValues::class, 'getAllRecords']);
    Route::get('/{key}', [KeyValues::class, 'show']);
});

Route::get('health-check', [KeyValues::class, 'healthCheck']);