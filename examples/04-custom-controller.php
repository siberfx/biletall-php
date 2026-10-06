<?php

/**
 * Use the client in your own controller instead of the package routes.
 *
 * Disable the package routes with BILETALL_ROUTES=false, then register:
 *   Route::get('/trips', TripController::class);
 */

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Siberfx\BiletAll\Services\BiletAllClient;

class TripController
{
    public function __construct(private readonly BiletAllClient $biletAll) {}

    public function __invoke(Request $request): JsonResponse
    {
        $input = $request->validate([
            'from' => ['required', 'integer'],
            'to' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        // Trips change often, a short cache keeps repeated searches cheap.
        $trips = Cache::remember(
            "trips:{$input['from']}:{$input['to']}:{$input['date']}",
            now()->addMinutes(5),
            fn (): array => $this->search($input, $request->ip()),
        );

        return response()->json(['data' => $trips]);
    }

    /**
     * @param  array{from: int, to: int, date: string}  $input
     * @return list<array<string, mixed>>
     */
    private function search(array $input, ?string $ip): array
    {
        $rows = $this->biletAll->request('Sefer', [
            'FirmaNo' => 0,
            'KalkisNoktaID' => $input['from'],
            'VarisNoktaID' => $input['to'],
            'Tarih' => $input['date'],
            'AraNoktaGelsin' => 0,
            'IslemTipi' => 0,
            'YolcuSayisi' => 1,
            'Ip' => $ip,
        ])['NewDataSet']['Table'] ?? [];

        $rows = array_is_list($rows) ? $rows : [$rows];

        return array_map(fn (array $trip): array => [
            'id' => $trip['ID'],
            'company' => $trip['FirmaAdi'],
            'departure' => $trip['Saat'],
            'price' => (float) $trip['BiletFiyatiInternet'],
            'empty_seats' => (int) ($trip['SeferBosKoltukSayisi'] ?? 0),
        ], $rows);
    }
}
