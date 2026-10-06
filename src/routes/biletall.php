<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Siberfx\BiletAll\Http\Controllers\BusResourceController;

// BUS API RESOURCES — prefix and middleware come from config('biletall.routes').
Route::controller(BusResourceController::class)->name('biletall.')->group(function (): void {
    Route::get('kara-noktasi-getir', 'index')->name('locations');
    Route::get('kara-noktasi-bul', 'search')->name('locations.search');
    Route::post('sefer-ara', 'searchSefer')->name('trips.search');
    Route::post('otobus-detay', 'searchOtobusFirma')->name('bus.detail');
    Route::get('otobus-koltuk-kontrol', 'searchOtobusKoltukKontrol')->name('bus.seat-check');
    Route::get('sefer-guzergah-sorgula', 'getGuzergahSorgula')->name('trips.route');
    Route::get('otobus-komisyon-sorgula', 'getOtobusFirmaKomisyon')->name('bus.commissions');
    Route::post('pnr-sorgu', 'searchPNR')->name('pnr.show');
    Route::post('pnr-iptal', 'cancel')->name('pnr.cancel');
    Route::post('islem-satis', 'IslemSatis')->name('sale');
});
