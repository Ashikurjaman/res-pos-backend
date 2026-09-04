<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FoodTypeController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\OutletRequestController;
use App\Http\Controllers\OutletDespatchController;
use App\Http\Controllers\OutletReceiveController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==================== CORS OPTIONS ====================
Route::options('/{any}', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN')
        ->header('Access-Control-Allow-Credentials', 'false');
})->where('any', '.*');

// ==================== PUBLIC AUTH ROUTES (no auth needed) ====================
Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup'])
        ->middleware('throttle:signup');

    Route::post('/signin', [AuthController::class, 'signin'])
        ->middleware('throttle:signin');
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

    // ---- Users (with rate limiting) ----
    Route::prefix('users')->middleware('throttle:user-management')->group(function () {
        Route::post('/bulk-delete', [AuthController::class, 'bulkDelete']);
        Route::put('/{id}/status', [AuthController::class, 'updateStatus']);
        Route::put('/{id}/role', [AuthController::class, 'updateRole']);
        Route::put('/{id}/permissions', [AuthController::class, 'updatePermissions']);
        Route::get('/', [AuthController::class, 'index']);
        Route::post('/', [AuthController::class, 'store']);
        Route::get('/{id}', [AuthController::class, 'show']);
        Route::put('/{id}', [AuthController::class, 'update']);
        Route::delete('/{id}', [AuthController::class, 'destroy']);
    });

    // ---- Roles & Permissions ----
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store'])->middleware('throttle:user-management');
        Route::get('/permissions-list', [RoleController::class, 'permissionsList']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update'])->middleware('throttle:user-management');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('throttle:user-management');
    });

    Route::get('/permissions', [RoleController::class, 'permissions']);

    // ---- Supplier Routes ----
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/all', [SupplierController::class, 'getAll']);
        Route::post('/', [SupplierController::class, 'store'])->middleware('throttle:product-update');
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('throttle:product-update');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('throttle:product-update');
        Route::post('/{id}/restore', [SupplierController::class, 'restore']);
        Route::get('/{id}/ledger', [SupplierController::class, 'getLedger']);
    });

    // ---- Products (with rate limiting) ----
    Route::prefix('products')->group(function () {
        Route::get('/create-data', [ProductController::class, 'create']);
        Route::get('/search', [ProductController::class, 'search']);
        Route::post('/', [ProductController::class, 'store'])->middleware('throttle:product-update');
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/with-stock', [ProductController::class, 'getWithStock']);
        Route::get('/by-category', [ProductController::class, 'getProductsByCategory']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::get('/{id}/stock', [ProductController::class, 'getStock']);
        Route::put('/{id}', [ProductController::class, 'update'])->middleware('throttle:product-update');
        Route::put('/{id}/stock', [ProductController::class, 'updateStock'])->middleware('throttle:stock-update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('throttle:product-update');
        Route::post('/{id}/restore', [ProductController::class, 'restore']);
    });

    // ---- Category ----
    Route::prefix('category')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/all', [CategoryController::class, 'getAll']);
        Route::get('/active', [CategoryController::class, 'getActive']);
        Route::post('/', [CategoryController::class, 'store'])->middleware('throttle:product-update');
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update'])->middleware('throttle:product-update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('throttle:product-update');
        Route::post('/{id}/restore', [CategoryController::class, 'restore']);
    });

    // ---- Unit ----
    Route::prefix('unit')->group(function () {
        Route::get('/', [UnitController::class, 'index']);
        Route::get('/all', [UnitController::class, 'getAll']);
        Route::get('/active', [UnitController::class, 'getActive']);
        Route::post('/', [UnitController::class, 'store'])->middleware('throttle:product-update');
        Route::get('/{id}', [UnitController::class, 'show']);
        Route::put('/{id}', [UnitController::class, 'update'])->middleware('throttle:product-update');
        Route::delete('/{id}', [UnitController::class, 'destroy'])->middleware('throttle:product-update');
        Route::post('/{id}/restore', [UnitController::class, 'restore']);
    });

    // ---- Tables ----
    Route::prefix('tables')->group(function () {
        Route::get('/', [TableController::class, 'index']);
        Route::get('/all', [TableController::class, 'getAll']);
        Route::post('/', [TableController::class, 'store'])->middleware('throttle:product-update');
        Route::get('/statistics', [TableController::class, 'statistics']);
        Route::put('/bulk/status', [TableController::class, 'bulkUpdateStatus'])->middleware('throttle:product-update');
        Route::get('/{id}', [TableController::class, 'show']);
        Route::put('/{id}', [TableController::class, 'update'])->middleware('throttle:product-update');
        Route::put('/{id}/status', [TableController::class, 'updateStatus'])->middleware('throttle:product-update');
        Route::delete('/{id}', [TableController::class, 'destroy'])->middleware('throttle:product-update');
        Route::delete('/{id}/force', [TableController::class, 'forceDelete'])->middleware('throttle:product-update');
    });

    // ---- Sales / POS (with rate limiting) ----
    Route::prefix('sales')->group(function () {
        Route::post('/initialize', [SaleController::class, 'initialize'])->middleware('throttle:pos-orders');
        Route::get('/', [SaleController::class, 'index']);
        Route::get('/summary/today', [SaleController::class, 'todaySummary']);
        Route::get('/table/{tableId}/active', [SaleController::class, 'getActiveSaleByTable']);
        Route::get('/{id}', [SaleController::class, 'show']);
        Route::put('/{id}', [SaleController::class, 'autoSave'])->middleware('throttle:pos-orders');
        Route::put('/{id}/update', [SaleController::class, 'update'])->middleware('throttle:pos-orders');
        Route::put('/{id}/status', [SaleController::class, 'updateStatus'])->middleware('throttle:pos-orders');
        Route::delete('/{id}', [SaleController::class, 'destroy'])->middleware('throttle:pos-orders');
    });

    Route::prefix('sale-list')->group(function () {
        Route::get('/', [SaleController::class, 'index']);
        Route::delete('/{id}', [SaleController::class, 'destroy'])->middleware('throttle:pos-orders');
    });

    Route::post('/create-sale', [SaleController::class, 'store'])->middleware('throttle:pos-orders');

    // ---- Company Routes ----
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('/all', [CompanyController::class, 'getAll']);
        Route::get('/active', [CompanyController::class, 'getActive']);
        Route::get('/pay-type/{payType}', [CompanyController::class, 'getByPayType']);
        Route::post('/', [CompanyController::class, 'store'])->middleware('throttle:product-update');
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::put('/{id}', [CompanyController::class, 'update'])->middleware('throttle:product-update');
        Route::delete('/{id}', [CompanyController::class, 'destroy'])->middleware('throttle:product-update');
        Route::post('/{id}/restore', [CompanyController::class, 'restore']);
    });

    // ---- Outlets ----
    Route::prefix('outlets')->group(function () {
        Route::get('/', [OutletController::class, 'index']);
        Route::get('/all', [OutletController::class, 'getAll']);
        Route::get('/active', [OutletController::class, 'getActive']);
        Route::post('/', [OutletController::class, 'store'])->middleware('throttle:product-update');
        Route::get('/{id}', [OutletController::class, 'show']);
        Route::put('/{id}', [OutletController::class, 'update'])->middleware('throttle:product-update');
        Route::delete('/{id}', [OutletController::class, 'destroy'])->middleware('throttle:product-update');
        Route::post('/{id}/restore', [OutletController::class, 'restore']);
    });

    // ---- Food Types ----
    Route::prefix('food-types')->group(function () {
        Route::get('/', [FoodTypeController::class, 'index']);
        Route::get('/active', [FoodTypeController::class, 'getActive']);
        Route::post('/', [FoodTypeController::class, 'store'])->middleware('throttle:product-update');
        Route::get('/{id}', [FoodTypeController::class, 'show']);
        Route::put('/{id}', [FoodTypeController::class, 'update'])->middleware('throttle:product-update');
        Route::delete('/{id}', [FoodTypeController::class, 'destroy'])->middleware('throttle:product-update');
        Route::post('/{id}/restore', [FoodTypeController::class, 'restore']);
        Route::post('/{id}/toggle', [FoodTypeController::class, 'toggleOnline'])->middleware('throttle:product-update');
    });

    // ============================================================
    // ✅ STOCK TRANSFER ROUTES (FIXED - No Duplicates)
    // ============================================================

    // ---- Stock Requests ----
    Route::prefix('stock-requests')->group(function () {
        Route::get('/', [OutletRequestController::class, 'index']);
        Route::post('/', [OutletRequestController::class, 'store']);
        Route::get('/pending-for-despatch', [OutletRequestController::class, 'pendingForDespatch']); // ✅ New route
        Route::get('/pending-count', [OutletRequestController::class, 'pendingCount']);
        Route::get('/{id}', [OutletRequestController::class, 'show']);
        Route::put('/{id}', [OutletRequestController::class, 'update']);
        Route::delete('/{id}', [OutletRequestController::class, 'destroy']);
        Route::post('/{id}/approve', [OutletRequestController::class, 'approve']);
        Route::put('/{id}/status', [OutletRequestController::class, 'updateStatus']);
    });

    // ---- Stock Despatches ----
    Route::prefix('stock-despatches')->group(function () {
        Route::get('/', [OutletDespatchController::class, 'index']);
        Route::post('/', [OutletDespatchController::class, 'store']);
        Route::get('/statistics', [OutletDespatchController::class, 'statistics']);
        Route::get('/{id}', [OutletDespatchController::class, 'show']);
        Route::put('/{id}/status', [OutletDespatchController::class, 'updateStatus']);
        Route::post('/{id}/cancel', [OutletDespatchController::class, 'cancel']);
    });

    // ---- Stock Receives ----
    Route::prefix('stock-receives')->group(function () {
        Route::get('/', [OutletReceiveController::class, 'index']);
        Route::post('/', [OutletReceiveController::class, 'store']);
        Route::get('/{id}', [OutletReceiveController::class, 'show']);
        Route::put('/{id}/status', [OutletReceiveController::class, 'updateStatus']);
    });
});
