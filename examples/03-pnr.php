<?php

/**
 * Look up a ticket by PNR and handle BiletAll being unavailable.
 */

use Illuminate\Support\Facades\Log;
use Siberfx\BiletAll\Exceptions\BiletAllException;
use Siberfx\BiletAll\Services\BiletAllClient;

try {
    $ticket = app(BiletAllClient::class)->request('PnrKonfirmasyon', [
        'Pnr' => 'ABC123',
        'Soyad' => 'Yılmaz',     // escaped automatically, special characters are safe
        'PnrYolcuId' => 0,
        'IslemTipi' => 0,
    ]);
} catch (BiletAllException $exception) {
    // $exception->command === 'PnrKonfirmasyon'; getPrevious() holds the SOAP error.
    Log::warning($exception->getMessage());

    // Not caught? Laravel renders it as a 502 JSON response.
    throw $exception;
}

dump($ticket);
