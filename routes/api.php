<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClaimController;
use App\Http\Controllers\Api\V1\ForecastController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\ReportingController;
use App\Http\Controllers\Api\V1\ShipmentController;
use Illuminate\Support\Facades\Route;

// Session and CSRF middleware are intentional: all module frontends live on the same Waypoint origin.
Route::middleware('web')->prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/auth/csrf', [AuthController::class, 'csrf'])->name('auth.csrf');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');

    Route::middleware('api.auth')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/reference/products', [AdminController::class, 'products'])->name('reference.products');
        Route::get('/reference/roles', [AdminController::class, 'roles'])
            ->middleware('role:admin')->name('reference.roles');
        Route::get('/reference/distributors', [AdminController::class, 'distributors'])
            ->middleware('role:admin,logistik pusat,sam')->name('reference.distributors');
        Route::get('/reference/regions', [AdminController::class, 'regions'])
            ->middleware('role:admin')->name('reference.regions');
        Route::get('/reference/fiscal-years', [AdminController::class, 'fiscalYears'])->name('reference.fiscal-years');

        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
            Route::get('/summary', [AdminController::class, 'summary'])->name('summary');
            Route::get('/users', [AdminController::class, 'users'])->name('users.index');
            Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
            Route::patch('/users/{id}', [AdminController::class, 'updateUser'])->whereNumber('id')->name('users.update');
            Route::get('/distributors', [AdminController::class, 'distributors'])->name('distributors.index');
            Route::post('/distributors', [AdminController::class, 'storeDistributor'])->name('distributors.store');
            Route::patch('/distributors/{id}', [AdminController::class, 'updateDistributor'])->whereNumber('id')->name('distributors.update');
            Route::get('/products', [AdminController::class, 'products'])->name('products.index');
            Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
            Route::patch('/products/{id}', [AdminController::class, 'updateProduct'])->whereNumber('id')->name('products.update');
            Route::get('/fiscal-years', [AdminController::class, 'fiscalYears'])->name('fiscal-years.index');
            Route::post('/fiscal-years', [AdminController::class, 'storeFiscalYear'])->name('fiscal-years.store');
            Route::patch('/fiscal-years/{id}', [AdminController::class, 'updateFiscalYear'])->whereNumber('id')->name('fiscal-years.update');
            Route::get('/regions', [AdminController::class, 'regions'])->name('regions.index');
            Route::post('/regions', [AdminController::class, 'storeRegion'])->name('regions.store');
            Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('activity-logs.index');
        });

        Route::prefix('forecasts')->name('forecasts.')->group(function (): void {
            Route::get('/recommendations', [ForecastController::class, 'recommendations'])
                ->middleware('role:distributor')->name('recommendations');
            Route::get('/', [ForecastController::class, 'index'])
                ->middleware('role:admin,distributor,logistik pusat,sam')->name('index');
            Route::get('/{forecast}', [ForecastController::class, 'show'])
                ->middleware('role:admin,distributor,logistik pusat,sam')->whereNumber('forecast')->name('show');
            Route::post('/', [ForecastController::class, 'store'])->middleware('role:distributor')->name('store');
            Route::post('/{forecast}/processing', [ForecastController::class, 'startProcessing'])
                ->middleware('role:logistik pusat')->whereNumber('forecast')->name('processing');
            Route::post('/{forecast}/approval', [ForecastController::class, 'approve'])
                ->middleware('role:logistik pusat')->whereNumber('forecast')->name('approve');
        });

        Route::prefix('purchase-orders')->name('purchase-orders.')->group(function (): void {
            Route::get('/', [PurchaseOrderController::class, 'index'])
                ->middleware('role:admin,distributor,logistik pusat,sam')->name('index');
            Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
                ->middleware('role:admin,distributor,logistik pusat,sam')->whereNumber('purchaseOrder')->name('show');
            Route::post('/', [PurchaseOrderController::class, 'store'])->middleware('role:distributor')->name('store');
            Route::post('/{purchaseOrder}/approval', [PurchaseOrderController::class, 'approve'])
                ->middleware('role:logistik pusat')->whereNumber('purchaseOrder')->name('approve');
        });

        Route::prefix('shipments')->name('shipments.')->group(function (): void {
            Route::get('/', [ShipmentController::class, 'index'])
                ->middleware('role:admin,distributor,logistik pusat,sam')->name('index');
            Route::post('/', [ShipmentController::class, 'store'])->middleware('role:logistik pusat')->name('store');
            Route::get('/{shipment}', [ShipmentController::class, 'show'])
                ->middleware('role:admin,distributor,logistik pusat,sam')->whereNumber('shipment')->name('show');
            Route::patch('/{shipment}', [ShipmentController::class, 'update'])
                ->middleware('role:logistik pusat')->whereNumber('shipment')->name('update');
            Route::post('/{shipment}/milestones', [ShipmentController::class, 'storeMilestone'])
                ->middleware('role:logistik pusat')->whereNumber('shipment')->name('milestones.store');
            Route::post('/{shipment}/receipt', [ShipmentController::class, 'storeReceipt'])
                ->middleware('role:distributor,logistik pusat')->whereNumber('shipment')->name('receipt.store');
        });

        Route::prefix('claims')->name('claims.')->group(function (): void {
            Route::get('/', [ClaimController::class, 'index'])
                ->middleware('role:admin,distributor,logistik pusat,sam')->name('index');
            Route::post('/', [ClaimController::class, 'store'])->middleware('role:distributor')->name('store');
            Route::get('/{claim}', [ClaimController::class, 'show'])
                ->middleware('role:admin,distributor,logistik pusat,sam')->whereNumber('claim')->name('show');
            Route::post('/{claim}/visit', [ClaimController::class, 'scheduleVisit'])
                ->middleware('role:sam')->whereNumber('claim')->name('visit');
            Route::post('/{claim}/verification', [ClaimController::class, 'verify'])
                ->middleware('role:sam')->whereNumber('claim')->name('verify');
            Route::post('/{claim}/return', [ClaimController::class, 'returnToDistributor'])
                ->middleware('role:sam')->whereNumber('claim')->name('return');
            Route::post('/{claim}/resolution', [ClaimController::class, 'resolve'])
                ->middleware('role:logistik pusat')->whereNumber('claim')->name('resolve');
            Route::get('/{claim}/photos/{photo}', [ClaimController::class, 'photo'])
                ->whereNumber('claim')->whereNumber('photo')->name('photos.show');
            Route::get('/{claim}/signatures/{certificate}', [ClaimController::class, 'signature'])
                ->whereNumber('claim')->whereNumber('certificate')->name('signature');
        });

        Route::prefix('reports')->name('reports.')->middleware('role:admin,distributor,logistik pusat,sam')->group(function (): void {
            Route::get('/dashboard', [ReportingController::class, 'dashboard'])->name('dashboard');
            Route::get('/summary', [ReportingController::class, 'summary'])->name('summary');
            Route::get('/sell-out', [ReportingController::class, 'sellOutHistory'])->name('sell-out.index');
            Route::get('/stock', [ReportingController::class, 'stockHistory'])->name('stock.index');
            Route::get('/export', [ReportingController::class, 'export'])->name('export');
            Route::post('/sell-out', [ReportingController::class, 'storeSellOut'])
                ->middleware('role:distributor')->name('sell-out.store');
            Route::post('/stock', [ReportingController::class, 'storeStock'])
                ->middleware('role:distributor')->name('stock.store');
        });
    });
});
