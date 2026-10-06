<?php

/**
 * Load the seat plan of a trip, then check whether the chosen seats are still free.
 *
 * Values come from a trip returned by the Sefer command (see 01-search-trips.php).
 */

use Siberfx\BiletAll\Services\BiletAllClient;

$biletAll = app(BiletAllClient::class);

$trip = [
    'FirmaNo' => 37,
    'KalkisNoktaID' => 738,
    'VarisNoktaID' => 84,
    'Tarih' => '2026-12-06',
    'Saat' => '2026-12-06T21:30:00+03:00',
    'HatNo' => 6,
    'IslemTipi' => 0,
    'SeferTakipNo' => '15039',
    'Ip' => '127.0.0.1',
];

// Bus detail: trip info, seats, travel types, features and payment rules.
$bus = $biletAll->request('Otobus', $trip)['Otobus'] ?? [];

$seats = $bus['Koltuk'] ?? [];
echo count($seats).' seats received'.PHP_EOL;

// Seat check. A list repeats the element: this sends
// <Koltuklar><Koltuk>…</Koltuk></Koltuklar><Koltuklar><Koltuk>…</Koltuk></Koltuklar>
// — adapt the structure to the format your BiletAll documentation expects.
$check = $biletAll->request('OtobusKoltukKontrol', [
    ...$trip,
    'Koltuklar' => [
        ['Koltuk' => ['KoltukNo' => 3, 'Cinsiyet' => 1]],
        ['Koltuk' => ['KoltukNo' => 4, 'Cinsiyet' => 2]],
    ],
]);

dump($check);
