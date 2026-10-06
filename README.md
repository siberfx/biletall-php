# Siberfx\BiletAll
Thus project is for demo purpose, if you want to have complete working code as in https://demo.ucuzyolu.com you can contact me.
what it includes;
 - Postman Endpoints Collections for ( BiletAll, BiletBank )
 - React code for front-end
 - Laravel 12 & 13 project with all working backend api.
 - Mentoring and Explainations during your development or you can hire me to achieve your goal.


## Requirements

| PHP  | Laravel    | SOAP transport                                                      |
| ---- | ---------- | ------------------------------------------------------------------- |
| 8.4+ | 12.x, 13.x | [siberfx/laravel-soap](https://github.com/siberfx/laravel-soap) ^1.0 |

The PHP `soap`, `dom` and `xmlwriter` extensions must be enabled.

## Install

``` bash
$ composer require siberfx/biletall-php
```

Add your credentials to `.env`:

``` dotenv
BILETALL_SANDBOX=true
BILETALL_WS_USERNAME=your-username
BILETALL_WS_PASSWORD=your-password
```

| Env variable                  | Default | Description                                         |
| ----------------------------- | ------- | --------------------------------------------------- |
| `BILETALL_SANDBOX`            | `false` | Use the test WSDL instead of the live one.          |
| `BILETALL_TEST_WSDL`          |         | Override the test WSDL URL.                         |
| `BILETALL_LIVE_WSDL`          |         | Override the live WSDL URL.                         |
| `BILETALL_TRACE`              | `false` | Keep the raw request/response XML (for debugging).  |
| `BILETALL_WSDL_CACHE`         | `3`     | `0` none, `1` disk, `2` memory, `3` both.           |
| `BILETALL_CONNECTION_TIMEOUT` | `30`    | Seconds to wait while connecting.                   |
| `BILETALL_VERIFY_SSL`         | `true`  | Set to `false` only for self-signed test hosts.     |
| `BILETALL_ROUTES`             | `true`  | Register the package routes.                        |
| `BILETALL_LOCATIONS_TTL`      | `3600`  | Seconds to cache the bus stop list.                 |
| `BILETALL_DEFAULT_FEATURE_FLAGS` | `11111111001000100…` | Reference OTipOzellik flag string (position = feature `tip`). |
| `BILETALL_USE_DEFAULT_FEATURE_FLAGS` | *(empty)* | Decode the reference flags instead of BiletAll's value. Empty = only in `local`. |

Publish the config file or the routes to customise them:

``` bash
$ php artisan vendor:publish --tag=biletall-config
$ php artisan vendor:publish --tag=biletall-routes   # routes/siberfx/biletall.php replaces the package routes
```

## Usage

The package registers these JSON endpoints under the `bus` prefix (see `biletall.routes`):

| Method | URI                            | BiletAll command       |
| ------ | ------------------------------ | ---------------------- |
| GET    | `bus/kara-noktasi-getir`       | `KaraNoktaGetirKomut`  |
| GET    | `bus/kara-noktasi-bul?term=…`  | `KaraNoktaGetirKomut`  |
| POST   | `bus/sefer-ara`                | `Sefer`                |
| POST   | `bus/otobus-detay`             | `Otobus`               |
| GET    | `bus/otobus-koltuk-kontrol`    | `OtobusKoltukKontrol`  |
| GET    | `bus/sefer-guzergah-sorgula`   | `Hat`                  |
| GET    | `bus/otobus-komisyon-sorgula`  | `OtobusFirmaKomisyonlar` |
| POST   | `bus/pnr-sorgu`                | `PnrKonfirmasyon`      |
| POST   | `bus/pnr-iptal`                | `PnrIslem`             |
| POST   | `bus/islem-satis`              | validation only — payment is not implemented (returns 501) |

Or send any command yourself:

``` php
use Siberfx\BiletAll\Services\BiletAllClient;

$result = app(BiletAllClient::class)->request('Sefer', [
    'FirmaNo' => 0,
    'KalkisNoktaID' => 738,
    'VarisNoktaID' => 84,
    'Tarih' => '2026-10-10',
    'AraNoktaGelsin' => 0,
    'IslemTipi' => 0,
    'YolcuSayisi' => 1,
    'Ip' => request()->ip(),
]);

$trips = $result['NewDataSet']['Table'] ?? [];
```

Parameter values are XML-escaped, `null` becomes an empty element and a list repeats the element.
A SOAP fault or unreadable response throws `Siberfx\BiletAll\Exceptions\BiletAllException`,
which Laravel renders as a `502` JSON response.

## Testing

The SOAP calls go through the `biletall` service of `siberfx/laravel-soap`, so they can be faked:

``` php
use Siberfx\Soap\Facades\Soap;

Soap::fake([
    'biletall.XmlIslet' => (object) ['XmlIsletResult' => (object) [
        'any' => '<NewDataSet><Table><ID>1</ID></Table></NewDataSet>',
    ]],
]);
```

Run the package tests:

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
