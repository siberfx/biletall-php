<?php

/**
 * Search trips between two bus stops and list them cheapest first.
 *
 * Run inside a Laravel app, e.g. paste into `php artisan tinker` or a route closure.
 */

use Siberfx\BiletAll\Helpers\BusSpecHelper;
use Siberfx\BiletAll\Services\BiletAllClient;

$biletAll = app(BiletAllClient::class);

// 1. Find the stop ids (KaraNoktaGetirKomut returns every bus stop).
$stops = $biletAll->request('KaraNoktaGetirKomut')['NewDataSet']['Table'] ?? [];

$findStop = fn (string $name): ?array => collect($stops)
    ->first(fn (array $stop): bool => str_contains(mb_strtolower($stop['Ad'] ?? ''), mb_strtolower($name)));

$from = $findStop('Ankara');
$to = $findStop('İzmir');

// 2. Search trips (Sefer) for a date.
$result = $biletAll->request('Sefer', [
    'FirmaNo' => 0,                  // 0 = all companies
    'KalkisNoktaID' => $from['ID'],
    'VarisNoktaID' => $to['ID'],
    'Tarih' => now()->addDay()->toDateString(),
    'AraNoktaGelsin' => 0,           // 1 = include connecting trips
    'IslemTipi' => 0,
    'YolcuSayisi' => 1,
    'Ip' => '127.0.0.1',
]);

// A single trip is returned as an associative array, several as a list.
$trips = $result['NewDataSet']['Table'] ?? [];
$trips = array_is_list($trips) ? $trips : [$trips];

// 3. Cheapest first, with decoded bus features.
collect($trips)
    ->sortBy(fn (array $trip): float => (float) $trip['BiletFiyatiInternet'])
    ->each(function (array $trip): void {
        $features = array_column(BusSpecHelper::handle($trip['OTipOzellik'] ?? ''), 'title');

        printf(
            "%s  %-25s %8s TL  %s\n",
            $trip['Saat'],
            $trip['FirmaAdi'],
            $trip['BiletFiyatiInternet'],
            implode(', ', $features),
        );
    });
