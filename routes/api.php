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
        Route::put('/{id}/permissions', [AuthController::class, 'updatePermissions']);
        Route::post('/bulk-delete', [AuthController::class, 'bulkDelete']);
    });

    // ---- Roles & Permissions (Dynamic RBAC) ----
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/permissions-list', [RoleController::class, 'permissionsList']); // must be before /{id}
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
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
        Route::get('/create-data', [ProductController::class, 'create']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/with-stock', [ProductController::class, 'getWithStock']);
        Route::get('/by-category', [ProductController::class, 'getProductsByCategory']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::get('/{id}/stock', [ProductController::class, 'getStock']); // ✅ Added
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::put('/{id}/stock', [ProductController::class, 'updateStock']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/{id}/restore', [ProductController::class, 'restore']);
    });

    // ---- Category ----
    Route::prefix('category')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/all', [CategoryController::class, 'getAll']);
        Route::get('/active', [CategoryController::class, 'getActive']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
        Route::post('/{id}/restore', [CategoryController::class, 'restore']);
    });

    // ---- Unit ----
    Route::prefix('unit')->group(function () {
        Route::get('/', [UnitController::class, 'index']);
        Route::get('/all', [UnitController::class, 'getAll']);
        Route::get('/active', [UnitController::class, 'getActive']);
        Route::post('/', [UnitController::class, 'store']);
        Route::get('/{id}', [UnitController::class, 'show']);
        Route::put('/{id}', [UnitController::class, 'update']);
        Route::delete('/{id}', [UnitController::class, 'destroy']);
        Route::post('/{id}/restore', [UnitController::class, 'restore']);
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
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('/all', [CompanyController::class, 'getAll']);
        Route::get('/active', [CompanyController::class, 'getActive']);
        Route::get('/pay-type/{payType}', [CompanyController::class, 'getByPayType']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::put('/{id}', [CompanyController::class, 'update']);
        Route::delete('/{id}', [CompanyController::class, 'destroy']);
        Route::post('/{id}/restore', [CompanyController::class, 'restore']);
    });

    Route::prefix('outlets')->group(function () {
        Route::get('/', [OutletController::class, 'index']);
        Route::get('/all', [OutletController::class, 'getAll']);
        Route::get('/active', [OutletController::class, 'getActive']);
        Route::post('/', [OutletController::class, 'store']);
        Route::get('/{id}', [OutletController::class, 'show']);
        Route::put('/{id}', [OutletController::class, 'update']);
        Route::delete('/{id}', [OutletController::class, 'destroy']);
        Route::post('/{id}/restore', [OutletController::class, 'restore']);
    });

    Route::prefix('food-types')->group(function () {
        Route::get('/', [FoodTypeController::class, 'index']);
        Route::get('/active', [FoodTypeController::class, 'getActive']);
        Route::post('/', [FoodTypeController::class, 'store']);
        Route::get('/{id}', [FoodTypeController::class, 'show']);
        Route::put('/{id}', [FoodTypeController::class, 'update']);
        Route::delete('/{id}', [FoodTypeController::class, 'destroy']);
        Route::post('/{id}/restore', [FoodTypeController::class, 'restore']);
        Route::post('/{id}/toggle', [FoodTypeController::class, 'toggleOnline']);
    });

    Route::prefix('stock-requests')->group(function () {
        Route::get('/', [OutletRequestController::class, 'index']);
        Route::post('/', [OutletRequestController::class, 'store']);
        Route::get('/pending-count', [OutletRequestController::class, 'pendingCount']);
        Route::get('/{id}', [OutletRequestController::class, 'show']);
        Route::post('/{id}/approve', [OutletRequestController::class, 'approve']);
    });

    // ============ DESPATCH ROUTES ============
    Route::prefix('stock-despatches')->group(function () {
        Route::get('/', [OutletDespatchController::class, 'index']);
        Route::post('/', [OutletDespatchController::class, 'store']);
        Route::get('/{id}', [OutletDespatchController::class, 'show']);
    });

    // ============ RECEIVE ROUTES ============
    Route::prefix('stock-receives')->group(function () {
        Route::get('/', [OutletReceiveController::class, 'index']);
        Route::post('/', [OutletReceiveController::class, 'store']);
        Route::get('/{id}', [OutletReceiveController::class, 'show']);
    });
});

// ==================== TEST ROUTES ====================
