<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MedicalController;
use App\Http\Controllers\NurseController;

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

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('patients', App\Http\Controllers\PatientController::class);
    Route::get('/patients-search', [App\Http\Controllers\PatientController::class, 'search'])->name('patients.search');
    Route::resource('referrals', App\Http\Controllers\ReferralController::class);
    Route::get('/referrals/{id}/pdf', [App\Http\Controllers\ReferralController::class, 'downloadPdf'])->name('referrals.pdf');
    Route::get('/referrals/{id}/pdf-essalud', [App\Http\Controllers\ReferralController::class, 'downloadPdfEssalud'])->name('referrals.pdf_essalud');

    Route::resource('orders', App\Http\Controllers\OrderController::class);
    Route::post('orders/store-bulk', [App\Http\Controllers\OrderController::class, 'storeBulk'])
        ->name('orders.store_bulk');
        
    Route::resource('medicals', App\Http\Controllers\MedicalController::class);
    Route::resource('nurses', NurseController::class);
    Route::get('/enfermeria/imprimir/{id}', [NurseController::class, 'printSingle'])->name('enfermeria.print.single');

});
