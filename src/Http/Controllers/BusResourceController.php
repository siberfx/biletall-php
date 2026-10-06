<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Siberfx\BiletAll\Helpers\BusSpecHelper;
use Siberfx\BiletAll\Services\BiletAllClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON endpoints for the BiletAll bus API.
 *
 * Transport errors surface as BiletAllException, which renders a 502 response.
 */
class BusResourceController extends Controller
{
    private const string NOT_FOUND = 'Hiç bir kayıt bulunamadı';

    /**
     * Fields of a Sefer row exposed by searchSefer().
     */
    private const array SEFER_FIELDS = [
        'ID', 'Vakit', 'FirmaNo', 'FirmaAdi', 'YerelSaat', 'YerelInternetSaat', 'Tarih', 'GunBitimi',
        'Saat', 'HatNo', 'IlkKalkisYeri', 'SonVarisYeri', 'KalkisYeri', 'VarisYeri', 'IlkKalkisNoktaID',
        'IlkKalkisNokta', 'KalkisNoktaID', 'KalkisNokta', 'VarisNoktaID', 'VarisNokta', 'SonVarisNoktaID',
        'SonVarisNokta', 'OtobusTipi', 'OtobusKoltukYerlesimTipi', 'OTipAciklamasi', 'OtobusTelefonu',
        'OtobusPlaka', 'SeyahatSuresi', 'SeyahatSuresiGosterimTipi', 'YaklasikSeyahatSuresi', 'BiletFiyati1',
        'BiletFiyatiInternet', 'Sinif_Farki', 'MaxRzvZamani', 'SeferTipi', 'SeferTipiAciklamasi',
        'HatSeferNo', 'O_Tip_Sinif', 'SeferTakipNo', 'ToplamSatisAdedi', 'DolulukKuraliVar', 'OTipOzellik',
        'NormalBiletFiyati', 'DoluSeferMi', 'Tesisler', 'SeferBosKoltukSayisi', 'KalkisTerminalAdi',
        'KalkisTerminalAdiSaatleri', 'MaximumRezerveTarihiSaati', 'Guzergah', 'KKZorunluMu',
        'BiletIptalAktifMi', 'AcikParaKullanimAktifMi', 'SefereKadarIptalEdilebilmeSuresiDakika',
        'FirmaSeferAciklamasi', 'SatisYonlendirilecekMi',
    ];

    /**
     * Passengers per sale; passenger 1 is required, the rest are optional.
     */
    private const int MAX_PASSENGERS = 4;

    public function __construct(
        protected BiletAllClient $biletAll,
    ) {}

    /**
     * All bus stops (cached, see biletall.locations_cache_ttl).
     */
    public function index(): JsonResponse
    {
        return $this->success($this->locations());
    }

    /**
     * Bus stops whose fields contain the "term" query parameter.
     */
    public function search(Request $request): JsonResponse
    {
        $term = Str::lower(trim((string) $request->query('term', '')));

        if (mb_strlen($term) < 2) {
            return $this->fail('Arama için en az 2 karakter girin', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $matches = array_filter(
            $this->locations(),
            static fn (array $location): bool => array_any(
                $location,
                static fn (mixed $value): bool => is_string($value) && str_contains(Str::lower($value), $term),
            ),
        );

        return $this->success(array_values($matches));
    }

    public function searchSefer(Request $request): JsonResponse
    {
        $input = $this->validated($request, [
            'KalkisNoktaID' => 'required',
            'VarisNoktaID' => 'required',
            'Tarih' => 'required',
        ]);

        $response = $this->biletAll->request('Sefer', [
            'FirmaNo' => 0, // burası sabit
            'KalkisNoktaID' => $input['KalkisNoktaID'],
            'VarisNoktaID' => $input['VarisNoktaID'],
            'Tarih' => $input['Tarih'],
            'AraNoktaGelsin' => 0, // 0: sadece seçilen kalkış/varış noktaları, 1: aktarmalılar da dahil.
            'IslemTipi' => 0, // burası sabit
            'YolcuSayisi' => 1,
            'Ip' => $request->ip(),
        ]);

        $trips = $this->rows(data_get($response, 'NewDataSet.Table'));

        if ($trips === []) {
            return $this->notFound();
        }

        return $this->success(array_map(static function (array $trip): array {
            $result = [];

            foreach (self::SEFER_FIELDS as $field) {
                $result[$field] = $trip[$field] ?? null;
            }

            $result['Ozellikler'] = BusSpecHelper::handle($trip['OTipOzellik'] ?? '');

            return $result;
        }, $trips));
    }

    public function searchOtobusFirma(Request $request): JsonResponse
    {
        $input = $this->validated($request, [
            'FirmaNo' => 'required',
            'KalkisNoktaID' => 'required',
            'VarisNoktaID' => 'required',
            'Tarih' => 'required',
            'Saat' => 'required',
            'SeferTakipNo' => 'required',
        ]);

        $bus = data_get($this->biletAll->request('Otobus', $this->seferParameters($input, $request)), 'Otobus');

        if (empty($bus['Sefer'])) {
            return $this->notFound();
        }

        return $this->success([
            'Otobus' => $bus['Sefer'],
            'Koltuk' => $bus['Koltuk'] ?? [],
            'SeyahatTipleri' => $bus['SeyahatTipleri'] ?? [],
            'OTipOzellik' => $bus['OTipOzellik'] ?? [],
            'OdemeKurallari' => $bus['OdemeKurallari'] ?? [],
        ]);
    }

    public function searchOtobusKoltukKontrol(Request $request): JsonResponse
    {
        $input = $this->validated($request, [
            'FirmaNo' => 'required',
            'KalkisNoktaID' => 'required',
            'VarisNoktaID' => 'required',
            'Tarih' => 'required',
            'Saat' => 'required',
            'SeferTakipNo' => 'required',
            'Koltuklar' => 'required',
        ]);

        return $this->success($this->biletAll->request('OtobusKoltukKontrol', [
            ...$this->seferParameters($input, $request),
            'Koltuklar' => $input['Koltuklar'],
        ]));
    }

    public function getGuzergahSorgula(Request $request): JsonResponse
    {
        $input = $this->validated($request, [
            'FirmaNo' => 'required',
            'HatNo' => 'required',
            'KalkisNoktaID' => 'required',
            'VarisNoktaID' => 'required',
            'SeferTakipNo' => 'required',
            'Tarih' => 'required',
        ]);

        $route = $this->rows(data_get($this->biletAll->request('Hat', [
            'FirmaNo' => $input['FirmaNo'],
            'HatNo' => $input['HatNo'],
            'KalkisNoktaID' => $input['KalkisNoktaID'],
            'VarisNoktaID' => $input['VarisNoktaID'],
            'BilgiIslemAdi' => 'GuzergahVerSaatli',
            'SeferTakipNo' => $input['SeferTakipNo'],
            'Tarih' => $input['Tarih'],
        ]), 'NewDataSet.Table1'));

        return $route === [] ? $this->notFound() : $this->success($route);
    }

    public function searchPNR(Request $request): JsonResponse
    {
        $input = $this->validated($request, [
            'Pnr' => 'required',
            'Soyad' => 'required',
            'PnrYolcuId' => 'required',
            'IslemTipi' => 'required',
        ]);

        $response = $this->biletAll->request('PnrKonfirmasyon', $input);

        return $response === [] ? $this->notFound() : $this->success($response);
    }

    public function cancel(Request $request): JsonResponse
    {
        $input = $this->validated($request, [
            'PnrNo' => 'required',
            'PnrKoltukNo' => 'required',
            'WebUyeNo' => 'required',
            'PnrIslemTip' => 'required',
            'PnrSatisIptalTutar' => 'required',
            'PnrAramaParametre' => 'required',
            'AcikParaIade' => 'required',
        ]);

        $response = $this->biletAll->request('PnrIslem', $input);

        return $response === [] ? $this->notFound() : $this->success($response);
    }

    /**
     * Validates a sale and builds the IslemSatis payload.
     *
     * Payment is not wired yet: collect the payment with your POS, then send
     * prepareDataForSale($request)['form'] with $biletAll->request('IslemSatis', ...).
     */
    public function IslemSatis(Request $request): JsonResponse
    {
        $this->validated($request, $this->saleRules());

        // @todo take the payment, then call $this->biletAll->request('IslemSatis', $this->prepareDataForSale($request)['form']).
        return $this->fail('Ödeme entegrasyonu henüz tamamlanmadı', Response::HTTP_NOT_IMPLEMENTED);
    }

    //@todo endpoint form data implementation awaits
    public function getOtobusFirmaKomisyon(): JsonResponse
    {
        return $this->success($this->biletAll->request('OtobusFirmaKomisyonlar'));
    }

    /**
     * Shared parameters of the Otobus and OtobusKoltukKontrol commands.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function seferParameters(array $input, Request $request): array
    {
        return [
            'FirmaNo' => $input['FirmaNo'], // 37
            'KalkisNoktaID' => $input['KalkisNoktaID'], // 738
            'VarisNoktaID' => $input['VarisNoktaID'], // 84
            'Tarih' => $input['Tarih'], // '2023-12-06'
            'Saat' => $input['Saat'], // '2023-12-06T21:30:00+02:00'
            'HatNo' => 6, // sabit değişken
            'IslemTipi' => 0, // sabit değişken
            'SeferTakipNo' => $input['SeferTakipNo'], // '15039'
            'Ip' => $request->ip(),
        ];
    }

    /**
     * @return array{form: array<string, mixed>, passengers: list<array<string, mixed>>}
     */
    protected function prepareDataForSale(Request $request): array
    {
        $busData = $request->only([
            'FirmaNo', 'KalkisNoktaID', 'VarisNoktaID', 'Tarih', 'Saat', 'HatNo', 'SeferNo', 'KalkisTerminalAdiSaatleri',
        ]);

        $passengersData = [];
        $passengers = [];

        foreach (range(1, self::MAX_PASSENGERS) as $i) {
            if (! $request->filled("KoltukNo{$i}")) {
                continue;
            }

            $passengersData += $request->only(["KoltukNo{$i}", "Adi{$i}", "Soyadi{$i}", "Cinsiyet{$i}", "TcKimlikNo{$i}"]);

            $passengers[] = [
                'name' => $request->input("Adi{$i}"),
                'surname' => $request->input("Soyadi{$i}"),
                'tcNo' => $request->input("TcKimlikNo{$i}"),
                'gender' => (int) $request->input("Cinsiyet{$i}") === 1 ? 'female' : 'male', // 1 bayan - 2 erkek
            ];
        }

        $foot = [
            ...$request->only([
                'TelefonNo', 'Cinsiyet', 'ToplamBiletFiyati', 'YolcuSayisi', 'BiletSeriNo',
                'OdemeSekli', 'FirmaAciklama', 'HatirlaticiNot', 'SeyahatTipi',
            ]),
            'WebYolcu' => [
                'WebUyeNo' => '0',
                'Ip' => $request->ip(),
                'Email' => $request->input('Email'),
                // Platform POS: the platform collects the payment and pays BiletAll in advance.
                'OnOdemeKullan' => '1',
                'OnOdemeTutar' => $request->input('ToplamBiletFiyati'),
            ],
        ];

        return [
            'form' => [...$busData, ...$passengersData, ...$foot],
            'passengers' => $passengers,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function saleRules(): array
    {
        $rules = [
            'Email' => 'required|email',
            'FirmaNo' => 'required|integer',
            'KalkisNoktaID' => 'required|integer',
            'VarisNoktaID' => 'required|integer',
            'Tarih' => 'required',
            'Saat' => 'required',
            'HatNo' => 'required',
            'SeferNo' => 'required',
            'KalkisTerminalAdiSaatleri' => 'nullable',
            'ToplamBiletFiyati' => 'required',
            'YolcuSayisi' => 'required',
            'BiletSeriNo' => 'required',
            'OdemeSekli' => 'required',
            'FirmaAciklama' => 'nullable',
            'HatirlaticiNot' => 'nullable',
            'SeyahatTipi' => 'nullable',
        ];

        foreach (range(1, self::MAX_PASSENGERS) as $i) {
            $required = $i === 1 ? 'required' : "required_with:KoltukNo{$i}";

            $rules["KoltukNo{$i}"] = $i === 1 ? 'required' : 'nullable';
            $rules["Adi{$i}"] = $required;
            $rules["Soyadi{$i}"] = $required;
            $rules["TcKimlikNo{$i}"] = $required;
            $rules["Cinsiyet{$i}"] = $required;
        }

        return $rules;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function locations(): array
    {
        return Cache::remember(
            'biletall.locations',
            (int) config('biletall.locations_cache_ttl', 3600),
            fn (): array => $this->rows(data_get($this->biletAll->request('KaraNoktaGetirKomut'), 'NewDataSet.Table')),
        );
    }

    /**
     * A repeated XML element is a list, a single one an associative array; always return a list.
     *
     * @return list<array<string, mixed>>
     */
    protected function rows(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     *
     * @throws HttpResponseException with a 422 JSON response when validation fails.
     */
    protected function validated(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new HttpResponseException($this->fail(
                'Geçersiz veri',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['errors' => $validator->errors()],
            ));
        }

        return $validator->validated();
    }

    protected function success(mixed $data): JsonResponse
    {
        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    protected function notFound(): JsonResponse
    {
        return $this->fail(self::NOT_FOUND, Response::HTTP_OK, ['data' => []]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function fail(string $message, int $status, array $extra = []): JsonResponse
    {
        return new JsonResponse(['success' => false, ...$extra, 'message' => $message], $status);
    }
}
