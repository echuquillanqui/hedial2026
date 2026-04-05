<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MedicalController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\ExtraMaterialController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\SedeSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth', 'ensure.sede'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/home/export/pdf', [App\Http\Controllers\HomeController::class, 'exportPdf'])->name('home.export.pdf');
    Route::get('/home/export/excel', [App\Http\Controllers\HomeController::class, 'exportExcel'])->name('home.export.excel');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/seleccionar-sede', [SedeSessionController::class, 'select'])->name('sede.select');
    Route::post('/seleccionar-sede', [SedeSessionController::class, 'store'])->name('sede.store');
});

Route::middleware(['auth', 'ensure.sede'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('sedes', SedeController::class)->only(['index', 'store', 'update']);
    Route::post('/users/roles', [UserController::class, 'storeRole'])->name('users.roles.store');
    Route::post('/users/permissions', [UserController::class, 'storePermission'])->name('users.permissions.store');
    Route::post('/users/bulk-permissions', [UserController::class, 'bulkAssignPermissions'])->name('users.bulk-permissions');
    Route::get('/users/permisos/gestion', [UserController::class, 'permissionsManager'])->name('users.permissions-manager');
    Route::post('/users/permisos/usuario', [UserController::class, 'updateUserPermissions'])->name('users.permissions-manager.update-user');
    Route::post('/users/permisos/masivo', [UserController::class, 'bulkUpdatePermissions'])->name('users.permissions-manager.bulk-update');

    Route::resource('patients', App\Http\Controllers\PatientController::class);
    Route::get('/patients-search', [App\Http\Controllers\PatientController::class, 'search'])->name('patients.search');
    Route::resource('referrals', App\Http\Controllers\ReferralController::class);
    Route::get('/referrals/{id}/pdf', [App\Http\Controllers\ReferralController::class, 'downloadPdf'])->name('referrals.pdf');
    Route::get('/referrals/{id}/pdf-essalud', [App\Http\Controllers\ReferralController::class, 'downloadPdfEssalud'])->name('referrals.pdf_essalud');
    Route::get('/cie10-search', [App\Http\Controllers\ReferralController::class, 'searchCie10'])->name('referrals.cie10.search');

    Route::resource('orders', App\Http\Controllers\OrderController::class);
    Route::post('orders/store-bulk', [App\Http\Controllers\OrderController::class, 'storeBulk'])
        ->name('orders.store_bulk');
        
    Route::resource('medicals', App\Http\Controllers\MedicalController::class);
    Route::resource('nurses', NurseController::class);
    Route::get('/enfermeria/imprimir/{id}', [NurseController::class, 'printSingle'])->name('enfermeria.print.single');

    Route::get('extra-materials', [ExtraMaterialController::class, 'index'])->name('extra-materials.index');
    Route::post('extra-materials', [ExtraMaterialController::class, 'store'])->name('extra-materials.store');
    Route::delete('extra-materials/{extraMaterial}', [ExtraMaterialController::class, 'destroy'])->name('extra-materials.destroy');
    Route::patch('extra-materials/base/{material}', [ExtraMaterialController::class, 'updateStock'])->name('extra-materials.base.update');
    Route::delete('extra-materials/base/{material}', [ExtraMaterialController::class, 'destroyBaseMaterial'])->name('extra-materials.base.destroy');
    Route::post('extra-materials/base', [ExtraMaterialController::class, 'storeBaseMaterial'])->name('extra-materials.base.store');
    Route::get('extra-materials/report/monthly', [ExtraMaterialController::class, 'monthlyReport'])->name('extra-materials.report.monthly');


    Route::get('almacen/solicitudes', [App\Http\Controllers\WarehouseRequestController::class, 'index'])->name('warehouse.requests.index');
    Route::post('almacen/materiales', [App\Http\Controllers\WarehouseRequestController::class, 'storeMaterial'])->name('warehouse.materials.store');
    Route::patch('almacen/stocks/{warehouseStock}', [App\Http\Controllers\WarehouseRequestController::class, 'updateStock'])->name('warehouse.stocks.update');
    Route::post('almacen/solicitudes', [App\Http\Controllers\WarehouseRequestController::class, 'store'])->name('warehouse.requests.store');
    Route::patch('almacen/solicitudes/{warehouseRequest}/estado', [App\Http\Controllers\WarehouseRequestController::class, 'updateStatus'])->name('warehouse.requests.update-status');
    Route::post('almacen/solicitudes/{warehouseRequest}/despacho', [App\Http\Controllers\WarehouseRequestController::class, 'dispatch'])->name('warehouse.requests.dispatch');
    Route::post('almacen/solicitudes/{warehouseRequest}/recepcion', [App\Http\Controllers\WarehouseRequestController::class, 'receive'])->name('warehouse.requests.receive');
    Route::get('almacen/solicitudes/{warehouseRequest}/imprimir-solicitud', [App\Http\Controllers\WarehouseRequestController::class, 'printRequest'])->name('warehouse.requests.print-request');
    Route::get('almacen/solicitudes/{warehouseRequest}/imprimir-despacho', [App\Http\Controllers\WarehouseRequestController::class, 'printDispatch'])->name('warehouse.requests.print-dispatch');
});
