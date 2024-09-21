<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/trial', function () {
    return response()->json(['message' => 'Hello World!'], 200);
});
Route::post('/register',[AuthController::class, 'handle_register']);
Route::post('/login', [AuthController::class, 'handle_login']);
Route::post('/delete',[AuthController::class, 'handle_register']);
route::get('/delete/{id}', [AuthController::class, 'delete_user']);
route::get('/all_users', [AuthController::class, 'List_user']);



