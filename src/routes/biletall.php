<?php

use Illuminate\Support\Facades\Route;
use Siberfx\BiletAll\app\Http\Controllers\BusResourceController;

// BUS API RESOURCES
Route::prefix('bus')->group(function () {
    Route::get('/kara-noktasi-getir', [BusResourceController::class, 'index']);
    Route::get('/kara-noktasi-bul', [BusResourceController::class, 'search']);
    Route::post('/sefer-ara', [BusResourceController::class, 'searchSefer']);
    Route::post('/otobus-detay', [BusResourceController::class, 'searchOtobusFirma']);
    Route::post('/pnr-sorgu', [BusResourceController::class, 'searchPNR']);
    Route::post('/pnr-iptal', [BusResourceController::class, 'cancel']);
    Route::get('/otobus-koltuk-kontrol', [BusResourceController::class, 'searchOtobusKoltukKontrol']);

    Route::post('/islem-satis', [BusResourceController::class, 'IslemSatis']);
    Route::get('/sefer-guzergah-sorgula', [BusResourceController::class, 'getGuzergahSorgula']);
    Route::get('/otobus-komisyon-sorgula', [BusResourceController::class, 'getOtobusFirmaKomisyon']);
    Route::match(['put', 'post', 'get'], 'bus-callback', [BusResourceController::class, 'paymentCallback'])->name('bus-callback');
//    Route::post('/payment-check', [BusResourceController::class, 'paymentCheck']);


});

