<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UnitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::options('/{any}', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN')
        ->header('Access-Control-Allow-Credentials', 'false');
})->where('any', '.*');

// ==================== PUBLIC AUTH ROUTES (no auth needed) ====================
Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/signin', [AuthController::class, 'signin']);
});

// ==================== EVERYTHING BELOW REQUIRES AUTH ====================
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ---- Auth (protected) ----
    Route::prefix('auth')->group(function () {
        Route::post('/signout', [AuthController::class, 'signout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // ---- Users ----
    Route::prefix('users')->group(function () {
        Route::get('/', [AuthController::class, 'index']);
        Route::post('/', [AuthController::class, 'store']);
        Route::get('/{id}', [AuthController::class, 'show']);
        Route::put('/{id}', [AuthController::class, 'update']);
        Route::delete('/{id}', [AuthController::class, 'destroy']);
        Route::put('/{id}/status', [AuthController::class, 'updateStatus']);
        Route::put('/{id}/role', [AuthController::class, 'updateRole']);
        Route::post('/bulk-delete', [AuthController::class, 'bulkDelete']);
    });


    // Supplier Routes
    Route::prefix('suppliers')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/all', [SupplierController::class, 'getAll']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::put('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
        Route::post('/{id}/restore', [SupplierController::class, 'restore']);
        Route::get('/{id}/ledger', [SupplierController::class, 'getLedger']);
    });

    // ---- Products ----
    Route::prefix('products')->group(function () {
        // ✅ This route should be GET, not POST
        Route::get('/next-code', [ProductController::class, 'create']);
        Route::get('/create-data', [ProductController::class, 'create']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/with-stock', [ProductController::class, 'getProductsWithStock']);
        Route::get('/by-category', [ProductController::class, 'getProductsByCategory']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::get('/{id}/stock', [ProductController::class, 'getStock']);
        Route::put('/{id}/stock', [ProductController::class, 'updateStock']);
    });
    Route::get('/products-load', [ProductController::class, 'getProduct']);

    // ---- Category ----
    // routes/api.php
    Route::prefix('category')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);           // Get active categories
        Route::get('/all', [CategoryController::class, 'getAll']);       // Get all categories
        Route::get('/active', [CategoryController::class, 'getActive']); // Get active categories
        Route::post('/', [CategoryController::class, 'store']);          // Create category
        Route::get('/{id}', [CategoryController::class, 'show']);        // Get single category
        Route::put('/{id}', [CategoryController::class, 'update']);      // Update category
        Route::delete('/{id}', [CategoryController::class, 'destroy']);  // Delete category
        Route::post('/{id}/restore', [CategoryController::class, 'restore']); // Restore category
    });

    // ---- Unit ----
    Route::prefix('unit')->group(function () {
        Route::get('/', [UnitController::class, 'index']);           // Get active units (status = 1)
        Route::get('/all', [UnitController::class, 'getAll']);       // Get all units
        Route::get('/active', [UnitController::class, 'getActive']); // Get active units
        Route::post('/', [UnitController::class, 'store']);          // Create unit
        Route::get('/{id}', [UnitController::class, 'show']);        // Get single unit
        Route::put('/{id}', [UnitController::class, 'update']);      // Update unit
        Route::delete('/{id}', [UnitController::class, 'destroy']);  // Delete unit
        Route::post('/{id}/restore', [UnitController::class, 'restore']); // Restore unit
    });

    // ---- Tables ----
    Route::prefix('tables')->group(function () {
        Route::get('/', [TableController::class, 'index']);
        Route::get('/all', [TableController::class, 'getAll']);
        Route::post('/', [TableController::class, 'store']);
        Route::get('/statistics', [TableController::class, 'statistics']);
        Route::put('/bulk/status', [TableController::class, 'bulkUpdateStatus']);
        Route::get('/{id}', [TableController::class, 'show']);
        Route::put('/{id}', [TableController::class, 'update']);
        Route::put('/{id}/status', [TableController::class, 'updateStatus']);
        Route::delete('/{id}', [TableController::class, 'destroy']);
        Route::delete('/{id}/force', [TableController::class, 'forceDelete']);
    });

    // ---- Sales ----
    Route::prefix('sales')->group(function () {
        Route::post('/initialize', [SaleController::class, 'initialize']);
        Route::get('/', [SaleController::class, 'index']);
        Route::get('/summary/today', [SaleController::class, 'todaySummary']);
        Route::get('/table/{tableId}/active', [SaleController::class, 'getActiveSaleByTable']);
        Route::get('/{id}', [SaleController::class, 'show']);
        Route::put('/{id}', [SaleController::class, 'autoSave']);
        Route::put('/{id}/update', [SaleController::class, 'update']);
        Route::put('/{id}/status', [SaleController::class, 'updateStatus']);
        Route::delete('/{id}', [SaleController::class, 'destroy']);
    });

    Route::prefix('sale-list')->group(function () {
        Route::get('/', [SaleController::class, 'index']);
        Route::delete('/{id}', [SaleController::class, 'destroy']);
    });

    Route::post('/create-sale', [SaleController::class, 'store']);

    // ==================== COMPANY ROUTES ====================
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);           // Get all companies
        Route::post('/', [CompanyController::class, 'store']);          // Create company
        Route::get('/active', [CompanyController::class, 'getActive']); // Get active companies
        Route::get('/{id}', [CompanyController::class, 'show']);        // Get single company
        Route::put('/{id}', [CompanyController::class, 'update']);      // Update company
        Route::delete('/{id}', [CompanyController::class, 'destroy']);  // Delete company
        Route::post('/{id}/restore', [CompanyController::class, 'restore']); // Restore company
    });

    Route::prefix('outlets')->group(function () {
        Route::get('/', [OutletController::class, 'index']);              // Get active outlets
        Route::get('/all', [OutletController::class, 'getAll']);          // Get all outlets
        Route::get('/active', [OutletController::class, 'getActive']);    // Get active outlets
        Route::post('/', [OutletController::class, 'store']);             // Create outlet
        Route::get('/{id}', [OutletController::class, 'show']);           // Get single outlet
        Route::put('/{id}', [OutletController::class, 'update']);         // Update outlet
        Route::delete('/{id}', [OutletController::class, 'destroy']);     // Delete outlet
        Route::post('/{id}/restore', [OutletController::class, 'restore']); // Restore outlet
    });
});

// ==================== TEST ROUTES ====================
