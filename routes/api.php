<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UnitController;
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

// ==================== PRODUCT ROUTES ====================
Route::prefix('products')->group(function () {
    Route::get('/next-code', [ProductController::class, 'create']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/with-stock', [ProductController::class, 'getProductsWithStock']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
    Route::get('/{id}/stock', [ProductController::class, 'getStock']);
    Route::put('/{id}/stock', [ProductController::class, 'updateStock']);
});
Route::get('/products-load', [ProductController::class, 'getProduct']);

// ==================== CATEGORY ROUTES ====================
Route::prefix('category')->group(function () {
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
});

// ==================== UNIT ROUTES ====================
Route::prefix('unit')->group(function () {
    Route::post('/', [UnitController::class, 'store']);
    Route::get('/', [UnitController::class, 'index']);
    Route::get('/{id}', [UnitController::class, 'show']);
    Route::put('/{id}', [UnitController::class, 'update']);
    Route::delete('/{id}', [UnitController::class, 'destroy']);
});

// ==================== TABLE ROUTES ====================
Route::prefix('tables')->group(function () {
    Route::get('/', [TableController::class, 'index']);
    Route::get('/all', [TableController::class, 'getAll']);
    Route::post('/', [TableController::class, 'store']);
    Route::get('/{id}', [TableController::class, 'show']);
    Route::put('/{id}', [TableController::class, 'update']);
    Route::put('/{id}/status', [TableController::class, 'updateStatus']);
    Route::delete('/{id}', [TableController::class, 'destroy']);
    Route::delete('/{id}/force', [TableController::class, 'forceDelete']);
    Route::get('/all', [TableController::class, 'getAll']);
    Route::get('/statistics', [TableController::class, 'statistics']);
    Route::put('/bulk/status', [TableController::class, 'bulkUpdateStatus']);
});

// ==================== SALE ROUTES ====================
Route::prefix('sales')->group(function () {
    Route::post('/initialize', [SaleController::class, 'initialize']);
    Route::put('/{id}', [SaleController::class, 'autoSave']);
    Route::get('/', [SaleController::class, 'index']);
    Route::get('/{id}', [SaleController::class, 'show']);
    Route::put('/{id}/update', [SaleController::class, 'update']);
    Route::put('/{id}/status', [SaleController::class, 'updateStatus']);
    Route::delete('/{id}', [SaleController::class, 'destroy']);
    Route::get('/table/{tableId}/active', [SaleController::class, 'getActiveSaleByTable']);
    Route::get('/summary/today', [SaleController::class, 'todaySummary']);
});

// ==================== SALE LIST ROUTES ====================
Route::prefix('sale-list')->group(function () {
    Route::get('/', [SaleController::class, 'index']);
    Route::delete('/{id}', [SaleController::class, 'destroy']);
});

// ==================== CREATE SALE ====================
Route::post('/create-sale', [SaleController::class, 'store']);

// ==================== TEST ROUTE ====================
Route::get('/test-tables', function () {
    try {
        $tables = \App\Models\Table::all();
        return response()->json([
            'status' => 'success',
            'data' => $tables
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }


});

Route::get('/test-cors', function () {
    return response()->json([
        'message' => 'CORS is working!',
        'status' => 'success'
    ]);



});

   // ==================== AUTH ROUTES ====================
Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/signin', [AuthController::class, 'signin']);
    Route::post('/signout', [AuthController::class, 'signout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
});

// ==================== USER MANAGEMENT ROUTES ====================
Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [AuthController::class, 'index']);                    // Get all users
    Route::post('/', [AuthController::class, 'store']);                   // Create user
    Route::get('/{id}', [AuthController::class, 'show']);                 // Get single user
    Route::put('/{id}', [AuthController::class, 'update']);               // Update user
    Route::delete('/{id}', [AuthController::class, 'destroy']);           // Delete user
    Route::put('/{id}/status', [AuthController::class, 'updateStatus']);  // Update status
    Route::put('/{id}/role', [AuthController::class, 'updateRole']);      // Update role
    Route::post('/bulk-delete', [AuthController::class, 'bulkDelete']);   // Bulk delete
});