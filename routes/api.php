<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('loan/download/', [LoanController::class, 'convertHtmlToPdf']);
Route::get('loan/export/', [LoanController::class, 'exportToExcel']);
Route::post('loan/lead-capture/', [LoanController::class, 'leadCapture']);
Route::post('loan/email-send/', [LoanController::class, 'trySendEmail']);
