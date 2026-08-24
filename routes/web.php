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
use App\Http\Controllers\OperationalAreaController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\LaboratoryOrderController;
use App\Http\Controllers\FuaConfigurationController;
use App\Http\Controllers\FuaController;
use App\Http\Controllers\NephrologyConsultationController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\InitialClinicalHistoryController;
use App\Http\Controllers\HemodialysisConsentController;

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
    Route::get('auditoria/historias', [AuditController::class, 'histories'])->name('audit.histories');
    Route::get('auditoria/fissal', [AuditController::class, 'fissal'])->name('audit.fissal');
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
    Route::resource('areas-operativas', OperationalAreaController::class)
        ->only(['index', 'store', 'update'])
        ->names('operational-areas');

    Route::get('catalogo', [CatalogController::class, 'index'])->name('catalog.index');
    Route::put('catalogo', [CatalogController::class, 'update'])->name('catalog.update');
    Route::get('fuas/configuracion', [FuaConfigurationController::class, 'edit'])->name('fuas.configuration.edit');
    Route::put('fuas/configuracion', [FuaConfigurationController::class, 'update'])->name('fuas.configuration.update');
    Route::get('fuas', [FuaController::class, 'index'])->name('fuas.index');
    Route::get('impresiones/fuas-hemodialisis', [FuaController::class, 'hemodialysisIndex'])->name('fuas.hemodialysis.index');
    Route::post('impresiones/fuas-hemodialisis/imprimir', [FuaController::class, 'bulkPdf'])->name('fuas.hemodialysis.bulk-pdf');
    Route::get('impresiones/fuas-consultas', [FuaController::class, 'nephrologyIndex'])->name('fuas.nephrology.index');
    Route::post('impresiones/fuas-consultas/imprimir', [FuaController::class, 'nephrologyBulkPdf'])->name('fuas.nephrology.bulk-pdf');
    Route::get('impresiones/fuas-multisectoriales', [FuaController::class, 'multisectorialIndex'])->name('fuas.multisectorial.index');
    Route::post('impresiones/fuas-multisectoriales/imprimir', [FuaController::class, 'multisectorialBulkPdf'])->name('fuas.multisectorial.bulk-pdf');
    Route::post('fuas/generar-multisectorial', [FuaController::class, 'bulkGenerate'])->name('fuas.multisectorial.generate-bulk');
    Route::post('orders/{order}/fua', [FuaController::class, 'generateForOrder'])->name('fuas.orders.generate');
    Route::post('fuas/{fua}/subsanacion', [FuaController::class, 'storeCorrection'])->name('fuas.corrections.store');
    Route::get('fuas/{fua}/vista-previa', [FuaController::class, 'preview'])->name('fuas.preview');
    Route::put('fuas/{fua}/responsable', [FuaController::class, 'updateResponsible'])->name('fuas.responsible.update');
    Route::get('fuas/{fua}/pdf', [FuaController::class, 'pdf'])->name('fuas.pdf');
    Route::get('catalogo/listado', [CatalogController::class, 'list'])->name('catalog.list');
    Route::get('catalogo/areas/{area}/editar', [CatalogController::class, 'editArea'])->name('catalog.areas.edit');
    Route::put('catalogo/areas/{area}', [CatalogController::class, 'updateArea'])->name('catalog.areas.update');
    Route::get('catalogo/perfiles/{profile}/editar', [CatalogController::class, 'editProfile'])->name('catalog.profiles.edit');
    Route::put('catalogo/perfiles/{profile}', [CatalogController::class, 'updateProfile'])->name('catalog.profiles.update');
    Route::delete('catalogo/perfiles/{profile}', [CatalogController::class, 'destroyProfile'])->name('catalog.profiles.destroy');
    Route::post('catalogo', [CatalogController::class, 'store'])->name('catalog.store');

    Route::get('laboratory/orders/create', [LaboratoryOrderController::class, 'create'])->name('laboratory.orders.create');
    Route::post('laboratory/orders', [LaboratoryOrderController::class, 'store'])->name('laboratory.orders.store');
    Route::post('laboratory/orders/import', [LaboratoryOrderController::class, 'import'])->name('laboratory.orders.import');
    Route::get('laboratory/results', [LaboratoryOrderController::class, 'results'])->name('laboratory.results.index');
    Route::get('laboratory/results/{laboratoryOrder}', [LaboratoryOrderController::class, 'show'])->name('laboratory.results.show');
    Route::get('laboratory/results/{laboratoryOrder}/pdf', [LaboratoryOrderController::class, 'pdf'])->name('laboratory.results.pdf');
    Route::post('laboratory/results/pdf/bulk', [LaboratoryOrderController::class, 'bulkPdf'])->name('laboratory.results.bulk-pdf');
    Route::put('laboratory/results/{laboratoryOrder}', [LaboratoryOrderController::class, 'updateResults'])->name('laboratory.results.update');

    Route::post('/users/roles', [UserController::class, 'storeRole'])->name('users.roles.store');
    Route::post('/users/permissions', [UserController::class, 'storePermission'])->name('users.permissions.store');
    Route::post('/users/bulk-permissions', [UserController::class, 'bulkAssignPermissions'])->name('users.bulk-permissions');
    Route::get('/users/permisos/gestion', [UserController::class, 'permissionsManager'])->name('users.permissions-manager');
    Route::post('/users/permisos/usuario', [UserController::class, 'updateUserPermissions'])->name('users.permissions-manager.update-user');
    Route::post('/users/permisos/masivo', [UserController::class, 'bulkUpdatePermissions'])->name('users.permissions-manager.bulk-update');

    Route::resource('patients', App\Http\Controllers\PatientController::class);
    Route::get('historias-iniciales/{initialHistory}/pdf', [InitialClinicalHistoryController::class, 'pdf'])->name('initial-histories.pdf');
    Route::resource('historias-iniciales', InitialClinicalHistoryController::class)->except(['destroy'])->parameters(['historias-iniciales' => 'initialHistory'])->names('initial-histories');
    Route::get('consentimientos/{consent}/pdf', [HemodialysisConsentController::class, 'pdf'])->name('consents.pdf');
    Route::resource('consentimientos', HemodialysisConsentController::class)->only(['index', 'create', 'store', 'show'])->parameters(['consentimientos' => 'consent'])->names('consents');
    Route::get('/patients-search', [App\Http\Controllers\PatientController::class, 'search'])->name('patients.search');
    Route::resource('referrals', App\Http\Controllers\ReferralController::class);
    Route::get('/referrals/{id}/pdf', [App\Http\Controllers\ReferralController::class, 'downloadPdf'])->name('referrals.pdf');
    Route::get('/referrals/{id}/pdf-essalud', [App\Http\Controllers\ReferralController::class, 'downloadPdfEssalud'])->name('referrals.pdf_essalud');
    Route::get('/cie10-search', [App\Http\Controllers\ReferralController::class, 'searchCie10'])->name('referrals.cie10.search');

    Route::get('orders/nephrology/create', [App\Http\Controllers\OrderController::class, 'createNephrology'])->name('orders.nephrology.create');
    Route::post('orders/nephrology', [App\Http\Controllers\OrderController::class, 'storeNephrology'])->name('orders.nephrology.store');
    Route::get('orders/multisectorial', [OrderController::class, 'multisectorialIndex'])->name('orders.multisectorial.index');
    Route::get('orders/multisectorial/create', [OrderController::class, 'createMultisectorial'])->name('orders.multisectorial.create');
    Route::post('orders/multisectorial', [OrderController::class, 'storeMultisectorial'])->name('orders.multisectorial.store');
    Route::resource('orders', App\Http\Controllers\OrderController::class);
    Route::post('orders/store-bulk', [App\Http\Controllers\OrderController::class, 'storeBulk'])
        ->name('orders.store_bulk');
        
    Route::resource('medicals', App\Http\Controllers\MedicalController::class);
    Route::get('consultas/{consultation}/consulta.pdf', [NephrologyConsultationController::class, 'consultationPdf'])->name('consultations.pdf');
    Route::get('consultas/{consultation}/receta.pdf', [NephrologyConsultationController::class, 'prescriptionPdf'])->name('consultations.prescription.pdf');
    Route::resource('consultas', NephrologyConsultationController::class)
        ->except(['show', 'destroy'])
        ->parameters(['consultas' => 'consultation'])
        ->names('consultations');
    Route::resource('nurses', NurseController::class);
    Route::post('enfermeria/modulo-diario', [NurseController::class, 'storeModuleAssignment'])
        ->name('nurses.module-assignment.store');
    Route::get('/enfermeria/imprimir-bloque/verificar', [NurseController::class, 'checkBulkPrint'])->name('enfermeria.print.bulk.check');
    Route::get('/enfermeria/imprimir-bloque', [NurseController::class, 'printBulk'])->name('enfermeria.print.bulk');
    Route::get('/enfermeria/imprimir/{id}', [NurseController::class, 'printSingle'])->name('enfermeria.print.single');

    Route::get('extra-materials', [ExtraMaterialController::class, 'index'])->name('extra-materials.index');
    Route::post('extra-materials', [ExtraMaterialController::class, 'store'])->name('extra-materials.store');
    Route::delete('extra-materials/{extraMaterial}', [ExtraMaterialController::class, 'destroy'])->name('extra-materials.destroy');
    Route::patch('extra-materials/base/{material}', [ExtraMaterialController::class, 'updateStock'])->name('extra-materials.base.update');
    Route::delete('extra-materials/base/{material}', [ExtraMaterialController::class, 'destroyBaseMaterial'])->name('extra-materials.base.destroy');
    Route::post('extra-materials/base', [ExtraMaterialController::class, 'storeBaseMaterial'])->name('extra-materials.base.store');
    Route::get('extra-materials/report/monthly', [ExtraMaterialController::class, 'monthlyReport'])->name('extra-materials.report.monthly');


    Route::get('almacen/dashboard', [App\Http\Controllers\WarehouseRequestController::class, 'dashboard'])->name('warehouse.dashboard');
    Route::get('almacen/configuracion', [App\Http\Controllers\WarehouseRequestController::class, 'configuration'])->name('warehouse.configuration.edit');
    Route::put('almacen/configuracion', [App\Http\Controllers\WarehouseRequestController::class, 'updateConfiguration'])->name('warehouse.configuration.update');
    Route::get('almacen/solicitudes', [App\Http\Controllers\WarehouseRequestController::class, 'index'])->name('warehouse.requests.index');
    Route::get('almacen/solicitudes-por-area', [App\Http\Controllers\WarehouseRequestController::class, 'byArea'])->name('warehouse.requests.by-area');
    Route::get('almacen/categorias', [App\Http\Controllers\WarehouseRequestController::class, 'categories'])->name('warehouse.categories.index');
    Route::post('almacen/categorias', [App\Http\Controllers\WarehouseRequestController::class, 'storeCategory'])->name('warehouse.categories.store');
    Route::get('almacen/materiales', [App\Http\Controllers\WarehouseRequestController::class, 'materials'])->name('warehouse.materials.index');
    Route::post('almacen/materiales', [App\Http\Controllers\WarehouseRequestController::class, 'storeMaterial'])->name('warehouse.materials.store');
    Route::put('almacen/materiales/{warehouseMaterial}', [App\Http\Controllers\WarehouseRequestController::class, 'updateMaterial'])->name('warehouse.materials.update');
    Route::get('almacen/ingresos', [App\Http\Controllers\WarehouseRequestController::class, 'entries'])->name('warehouse.entries.index');
    Route::post('almacen/ingresos', [App\Http\Controllers\WarehouseRequestController::class, 'storeEntry'])->name('warehouse.entries.store');
    Route::get('almacen/proveedores', [App\Http\Controllers\WarehouseRequestController::class, 'suppliers'])->name('warehouse.suppliers.index');
    Route::post('almacen/proveedores', [App\Http\Controllers\WarehouseRequestController::class, 'storeSupplier'])->name('warehouse.suppliers.store');
    Route::patch('almacen/materiales/{warehouseMaterial}/consumo', [App\Http\Controllers\WarehouseRequestController::class, 'updateAutomaticConsumption'])->name('warehouse.materials.consumption');
    Route::get('almacen/stocks', [App\Http\Controllers\WarehouseRequestController::class, 'stocks'])->name('warehouse.stocks.index');
    Route::get('almacen/movimientos', [App\Http\Controllers\WarehouseRequestController::class, 'movements'])->name('warehouse.movements.index');
    Route::patch('almacen/stocks/{warehouseStock}', [App\Http\Controllers\WarehouseRequestController::class, 'updateStock'])->name('warehouse.stocks.update');
    Route::get('almacen/alertas/descargar', [App\Http\Controllers\WarehouseRequestController::class, 'downloadAlerts'])->name('warehouse.alerts.download');
    Route::post('almacen/solicitudes', [App\Http\Controllers\WarehouseRequestController::class, 'store'])->name('warehouse.requests.store');
    Route::patch('almacen/solicitudes/{warehouseRequest}/estado', [App\Http\Controllers\WarehouseRequestController::class, 'updateStatus'])->name('warehouse.requests.update-status');
    Route::post('almacen/solicitudes/{warehouseRequest}/despacho', [App\Http\Controllers\WarehouseRequestController::class, 'dispatch'])->name('warehouse.requests.dispatch');
    Route::post('almacen/solicitudes/{warehouseRequest}/recepcion', [App\Http\Controllers\WarehouseRequestController::class, 'receive'])->name('warehouse.requests.receive');
    Route::get('almacen/solicitudes/{warehouseRequest}/imprimir-solicitud', [App\Http\Controllers\WarehouseRequestController::class, 'printRequest'])->name('warehouse.requests.print-request');
    Route::get('almacen/solicitudes/{warehouseRequest}/imprimir-despacho', [App\Http\Controllers\WarehouseRequestController::class, 'printDispatch'])->name('warehouse.requests.print-dispatch');
});
