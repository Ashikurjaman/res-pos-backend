<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UnitController;
use App\Models\Unitl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//Product
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/next-code', [ProductController::class, 'create']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

//Category
Route::post('/category', [CategoryController::class, 'store']);
Route::get('/category', [CategoryController::class, 'index']);
Route::get('/category/{id}', [CategoryController::class, 'show']);
Route::put('/category/{id}', [CategoryController::class, 'update']);
Route::delete('/category/{id}', [CategoryController::class, 'destroy']);

//Unit
Route::post('/unit', [UnitController::class, 'store']);
Route::get('/unit', [UnitController::class, 'index']);
Route::get('/unit/{id}', [UnitController::class, 'show']);
Route::put('/unit/{id}', [UnitController::class, 'update']);
Route::delete('/unit/{id}', [UnitController::class, 'destroy']);


// sale

Route::get('/products-load', [ProductController::class, 'getProduct']);
