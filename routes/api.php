<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::get('getPharmacies', [ApiController::class, 'getPharmacies']);
Route::get('getPharmacyMasks', [ApiController::class, 'getPharmacyMasks']);
Route::get('getPharmaciesByMaskPriceAndAmount', [ApiController::class, 'getPharmaciesByMaskPriceAndAmount']);
Route::get('getUsersByDateRange', [ApiController::class, 'getUsersByDateRange']);
Route::get('getMaskTransactionsData', [ApiController::class, 'getMaskTransactionsData']);
Route::get('getPharmaciesOrMasks', [ApiController::class, 'getPharmaciesOrMasks']);
Route::get('getUser', [ApiController::class, 'getUser']);
Route::post('addUser', [ApiController::class, 'addUser']);
Route::post('updateUserCashBalance', [ApiController::class, 'updateUserCashBalance']);
Route::post('userPurchaseMasks', [ApiController::class, 'userPurchaseMasks']);