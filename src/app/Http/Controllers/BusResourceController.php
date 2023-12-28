<?php

namespace Siberfx\BiletAll\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Siberfx\BiletAll\app\Services\BiletAllSoapClient;
use Siberfx\BiletAll\Helpers\BusSpecHelper;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;


/**
 * Base Controller for the frontend
 */
class BusResourceController extends Controller
{
    protected $paymentSendCard = [];

    public function __construct(
        public $soapService = new BiletAllSoapClient()
    )
    {
    }

    public function index()
    {

        // fetch destinations by this route.

        return [];

    }

    public function search(Request $request)
    {

        $term = $request->input('term');

//        search endpoint
        return response()->json($term);


    }

    public function searchSefer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'KalkisNoktaID' => 'required',
            'VarisNoktaID' => 'required',
            'Tarih' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veri'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $soapRequest = $this->soapService->doRequestFor('Sefer', [
            'FirmaNo' => 0, // burası sabit
            'KalkisNoktaID' => $request->get('KalkisNoktaID'),
            'VarisNoktaID' => $request->get('VarisNoktaID'),
            'Tarih' => $request->get('Tarih'),
            'AraNoktaGelsin' => 0, // 0 olması halinde sadece seçilmiş kalkış ve varış noktası ile alakalı sonuç dönmesi için, 1 yazılırsa aktarmalılar da dahil olur.
            'IslemTipi' => 0, // burası sabit
            'YolcuSayisi' => 1,
            'Ip' => $request->ip(),
        ]);

        if (empty($soapRequest) || !isset($soapRequest['NewDataSet']['Table'])) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Hiç bir kayıt bulunamadı',
                'soapresponse' => $soapRequest,
            ]);
        }

        $result = $collection = collect($soapRequest['NewDataSet']['Table'])->map(fn($each) => [
            'ID' => $each['ID'],
            'Vakit' => $each['Vakit'],
            'FirmaNo' => $each['FirmaNo'],
            'FirmaAdi' => $each['FirmaAdi'],
            'YerelSaat' => $each['YerelSaat'],
            'YerelInternetSaat' => $each['YerelInternetSaat'],
            'Tarih' => $each['Tarih'],
            'GunBitimi' => $each['GunBitimi'],
            'Saat' => $each['Saat'],
            'HatNo' => $each['HatNo'],
            'IlkKalkisYeri' => $each['IlkKalkisYeri'],
            'SonVarisYeri' => $each['SonVarisYeri'],
            'KalkisYeri' => $each['KalkisYeri'],
            'VarisYeri' => $each['VarisYeri'],
            'IlkKalkisNoktaID' => $each['IlkKalkisNoktaID'],
            'IlkKalkisNokta' => $each['IlkKalkisNokta'],
            'KalkisNoktaID' => $each['KalkisNoktaID'],
            'KalkisNokta' => $each['KalkisNokta'],
            'VarisNoktaID' => $each['VarisNoktaID'],
            'VarisNokta' => $each['VarisNokta'],
            'SonVarisNoktaID' => $each['SonVarisNoktaID'],
            'SonVarisNokta' => $each['SonVarisNokta'],
            'OtobusTipi' => $each['OtobusTipi'],
            'OtobusKoltukYerlesimTipi' => $each['OtobusKoltukYerlesimTipi'],
            'OTipAciklamasi' => $each['OTipAciklamasi'],
            'OtobusTelefonu' => $each['OtobusTelefonu'],
            'OtobusPlaka' => $each['OtobusPlaka'],
            'SeyahatSuresi' => $each['SeyahatSuresi'],
            'SeyahatSuresiGosterimTipi' => $each['SeyahatSuresiGosterimTipi'],
            'YaklasikSeyahatSuresi' => $each['YaklasikSeyahatSuresi'],
            'BiletFiyati1' => $each['BiletFiyati1'],
            'BiletFiyatiInternet' => $each['BiletFiyatiInternet'],
            'Sinif_Farki' => $each['Sinif_Farki'],
            'MaxRzvZamani' => $each['MaxRzvZamani'],
            'SeferTipi' => $each['SeferTipi'],
            'SeferTipiAciklamasi' => $each['SeferTipiAciklamasi'],
            'HatSeferNo' => $each['HatSeferNo'],
            'O_Tip_Sinif' => $each['O_Tip_Sinif'],
            'SeferTakipNo' => $each['SeferTakipNo'],
            'ToplamSatisAdedi' => $each['ToplamSatisAdedi'],
            'DolulukKuraliVar' => $each['DolulukKuraliVar'],
            'OTipOzellik' => $each['OTipOzellik'],
            'Ozellikler' => BusSpecHelper::handle($each['OTipOzellik']),
            'NormalBiletFiyati' => $each['NormalBiletFiyati'],
            'DoluSeferMi' => $each['DoluSeferMi'],
            'Tesisler' => $each['Tesisler'],
            'SeferBosKoltukSayisi' => $each['SeferBosKoltukSayisi'],
            'KalkisTerminalAdi' => $each['KalkisTerminalAdi'],
            'KalkisTerminalAdiSaatleri' => $each['KalkisTerminalAdiSaatleri'],
            'MaximumRezerveTarihiSaati' => $each['MaximumRezerveTarihiSaati'],
            'Guzergah' => $each['Guzergah'],
            'KKZorunluMu' => $each['KKZorunluMu'],
            'BiletIptalAktifMi' => $each['BiletIptalAktifMi'],
            'AcikParaKullanimAktifMi' => $each['AcikParaKullanimAktifMi'],
            'SefereKadarIptalEdilebilmeSuresiDakika' => $each['SefereKadarIptalEdilebilmeSuresiDakika'],
            'FirmaSeferAciklamasi' => $each['FirmaSeferAciklamasi'],
            'SatisYonlendirilecekMi' => $each['SatisYonlendirilecekMi']
        ]);

        $lowestPrice = $collection->min('BiletFiyatiInternet') ?? 0;


        return response()->json([
            'success' => true,
            'data' => $result
        ]);


    }

    public function searchOtobusFirma(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'FirmaNo' => 'required',
            'KalkisNoktaID' => 'required',
            'VarisNoktaID' => 'required',
            'Tarih' => 'required',
            'Saat' => 'required',
            'SeferTakipNo' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veri'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $soapRequest = $this->soapService->doRequestFor('Otobus', [
            'FirmaNo' => $request->get('FirmaNo'), // 37,
            'KalkisNoktaID' => $request->get('KalkisNoktaID'), // 738,
            'VarisNoktaID' => $request->get('VarisNoktaID'), // 84,
            'Tarih' => $request->get('Tarih'), // '2023-12-06',
            'Saat' => $request->get('Saat'), // '2023-12-06T21:30:00+02:00',
            'HatNo' => 6, // sabit değişken
            'IslemTipi' => 0, // sabit değişken
            'SeferTakipNo' => $request->get('SeferTakipNo'), // '15039',
//            'YolcuSayisi' => 1, // @todo opsiyonel
            'Ip' => $request->ip(),

        ]);

        if (empty($soapRequest) || !isset($soapRequest['Otobus']['Sefer'])) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Hiç bir kayıt bulunamadı'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'Otobus' => $soapRequest['Otobus']['Sefer'],
                'Koltuk' => $soapRequest['Otobus']['Koltuk'],
                'SeyahatTipleri' => $soapRequest['Otobus']['SeyahatTipleri'],
                'OTipOzellik' => $soapRequest['Otobus']['OTipOzellik'],
                'OdemeKurallari' => $soapRequest['Otobus']['OdemeKurallari'],
            ]
        ]);

    }

    public function searchOtobusKoltukKontrol(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'FirmaNo' => 'required',
            'KalkisNoktaID' => 'required',
            'VarisNoktaID' => 'required',
            'Tarih' => 'required',
            'Saat' => 'required',
            'SeferTakipNo' => 'required',
            'Koltuklar' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veri'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->soapService->doRequestFor('OtobusKoltukKontrol', [
            'FirmaNo' => $request->input('FirmaNo'), // 37,
            'KalkisNoktaID' => $request->input('KalkisNoktaID'), // 738,
            'VarisNoktaID' => $request->input('VarisNoktaID'), // 84,
            'Tarih' => $request->input('Tarih'), // '2023-12-06',
            'Saat' => $request->input('Saat'), // '2023-12-06T21:30:00+02:00',
            'HatNo' => 6, // sabit değişken
            'IslemTipi' => 0, // sabit değişken
            'SeferTakipNo' => $request->input('SeferTakipNo'), // '15039',
            'Ip' => $request->ip(),
            'Koltuklar' => $request->get('Koltuklar')

        ]);

    }


    public function getGuzergahSorgula(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'FirmaNo' => 'required',
            'HatNo' => 'required',
            'KalkisNoktaID' => 'required',
            'VarisNoktaID' => 'required',
            'SeferTakipNo' => 'required',
            'Tarih' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veri'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $soapRequest = $this->soapService->doRequestFor('Hat', [
            'FirmaNo' => $request->get('FirmaNo'),
            'HatNo' => $request->get('HatNo'),
            'KalkisNoktaID' => $request->get('KalkisNoktaID'),
            'VarisNoktaID' => $request->get('VarisNoktaID'),
            'BilgiIslemAdi' => 'GuzergahVerSaatli',
            'SeferTakipNo' => $request->get('SeferTakipNo'),
            'Tarih' => $request->get('Tarih'),
        ]);


        if (empty($soapRequest) && $soapRequest['NewDataSet']['Table1']) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Hiç bir kayıt bulunamadı'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $soapRequest['NewDataSet']['Table1']
        ]);

    }


    public function searchPNR(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'Pnr' => 'required',
            'Soyad' => 'required',
            'PnrYolcuId' => 'required',
            'IslemTipi' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veri'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $soapRequest = $this->soapService->doRequestFor('PnrKonfirmasyon', [
            'Pnr' => $request->get('Pnr'),
            'Soyad' => $request->get('Soyad'),
            'PnrYolcuId' => $request->get('PnrYolcuId'),
            'IslemTipi' => $request->get('IslemTipi'),

        ]);

        if (empty($soapRequest) || !isset($soapRequest['Otobus']['Sefer'])) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Hiç bir kayıt bulunamadı'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'Otobus' => $soapRequest['Otobus']['Sefer'],
                'Koltuk' => $soapRequest['Otobus']['Koltuk'],
                'SeyahatTipleri' => $soapRequest['Otobus']['SeyahatTipleri'],
                'OTipOzellik' => $soapRequest['Otobus']['OTipOzellik'],
                'OdemeKurallari' => $soapRequest['Otobus']['OdemeKurallari'],
            ]
        ]);

    }

    public function cancel(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'PnrNo' => 'required',
            'PnrKoltukNo' => 'required',
            'WebUyeNo' => 'required',
            'PnrIslemTip' => 'required',
            'PnrSatisIptalTutar' => 'required',
            'PnrAramaParametre' => 'required',
            'AcikParaIade' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veri'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $soapRequest = $this->soapService->doRequestFor('PnrIslem', [
            'PnrNo' => $request->get('PnrNo'),
            'PnrKoltukNo' => $request->get('PnrKoltukNo'),
            'WebUyeNo' => $request->get('WebUyeNo'),
            'PnrIslemTip' => $request->get('PnrIslemTip'),
            'PnrSatisIptalTutar' => $request->get('PnrSatisIptalTutar'),
            'PnrAramaParametre' => $request->get('PnrAramaParametre'),
            'AcikParaIade' => $request->get('AcikParaIade'),

        ]);

        if (empty($soapRequest)) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Hiç bir kayıt bulunamadı'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $soapRequest
        ]);

    }


    public function IslemSatis(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'Email' => 'required|email',
            'FirmaNo' => 'required|integer',
            'KalkisNoktaID' => 'required|integer',
            'VarisNoktaID' => 'required|integer',
            'Tarih' => 'required',
            'Saat' => 'required',
            'HatNo' => 'required',
            'SeferNo' => 'required',
            'KalkisTerminalAdiSaatleri' => 'nullable',

            'KoltukNo1' => 'required',
            'Adi1' => 'required',
            'Soyadi1' => 'required',
            'TcKimlikNo1' => 'required',
            'Cinsiyet1' => 'required',


            'KoltukNo3' => 'sometimes|required',
            'Adi3' => 'sometimes|required',
            'Soyadi3' => 'sometimes|required',
            'TcKimlikNo3' => 'sometimes|required',
            'Cinsiyet3' => 'sometimes|required',

            'KoltukNo4' => 'sometimes|required',
            'Adi4' => 'sometimes|required',
            'Soyadi4' => 'sometimes|required',
            'TcKimlikNo4' => 'sometimes|required',
            'TelefonNo4' => 'sometimes|required',
            'Cinsiyet4' => 'sometimes|required',

            'ToplamBiletFiyati' => 'required',
            'YolcuSayisi' => 'required',
            'BiletSeriNo' => 'required',
            'OdemeSekli' => 'required',
            'FirmaAciklama' => 'nullable',
            'HatirlaticiNot' => 'nullable',
            'SeyahatTipi' => 'nullable'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz data',
                'errors' => $validate->errors()
            ]);
        }


        $data = $this->prepareDataForSale($request);
        // $this->soapService->doRequestFor('IslemSatis', $data['form'], true);
        // return $this->soapService->doRequestFor('IslemSatis', $data['form']);

        $payPrice = $request->get('ToplamBiletFiyati');

        $dateExplode = explode('.', $request->get('KrediKartGecerlilikTarihi'));
        $cardInfo = [
            'cardHolderName' => $request->get('KrediKartSahip'),
            'cardNumber' => $request->get('KrediKartNo'),
            'month' => $dateExplode[0],
            'year' => substr($dateExplode[1], -2),
            'cvv' => $request->get('KrediKartCCV2'),
        ];
        $order = [
            'orderID' => $data['order']['id'],
            'price' => $request->get('ToplamBiletFiyati'),
            'product' => $request->get('product'),
            'qtt' => $request->get('YolcuSayisi', 1) ?? 1,
            'billingAddress1' => $request->get('invoice')['address'] ?? '',
            'city' => $request->get('invoice')['city']['name'] ?? '',
            'country' => $request->get('invoice')['country']['name'] ?? '',
            'postCode' => $request->get('invoice')['zip_code'] ?? '',
            'installmentCount' => $request->installmentCount ?? 1,
            'ipAddress' => $request->ip()
        ];


        $explodeNamer = explode(' ', trim($request->get('KrediKartSahip')));
        $lastName = end($explodeNamer);
        $firstName = str_replace($lastName, '', $request->get('KrediKartSahip'));
        $customer = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $request->get('WebYolcu')['Email'] ?? 'info@ucuzyolu.com',
            'phone' => $request->get('TelefonNo')
        ];

        $threeDS = 'Payment Process';

        return response()->json($threeDS);


        /* $response2 = $data2 = [];
         if ($response && isset($response['IslemSonuc'])) {
             if ($response['IslemSonuc']['Sonuc'] == 'true') {
                 $this->paymentSendCard = $response['IslemSonuc'];
                 $data2 = $this->prepareDataForSale($request);
                 $response2 = $this->soapService->doRequestFor('IslemSatis', $data['form']);
             } else {
                 $response['IslemSonuc']['fakke'] = "fake";
                 return $response['IslemSonuc'];
             }
         }*/


    }


    //@todo endpoint form data implementation awaits
    public function getOtobusFirmaKomisyon()
    {
        return $this->soapService->doRequestFor('OtobusFirmaKomisyonlar');

    }

    protected function prepareDataForSale(Request $request): array
    {
        $passengers = [];

        $busData = [
            'FirmaNo' => $request->get('FirmaNo'),
            'KalkisNoktaID' => $request->get('KalkisNoktaID'),
            'VarisNoktaID' => $request->get('VarisNoktaID'),
            'Tarih' => $request->get('Tarih'),
            'Saat' => $request->get('Saat'),
            'HatNo' => $request->get('HatNo'),
            'SeferNo' => $request->get('SeferNo'),
            'KalkisTerminalAdiSaatleri' => $request->get('KalkisTerminalAdiSaatleri'),
        ];

        $passengersData = [
            'KoltukNo1' => $request->get('KoltukNo1'),
            'Adi1' => $request->get('Adi1'),
            'Soyadi1' => $request->get('Soyadi1'),
            'TcKimlikNo1' => $request->get('TcKimlikNo1'),

        ];

        $passengers[] = [
            'name' => $request->get('Adi1'),
            'surname' => $request->get('Soyadi1'),
            'tcNo' => $request->get('TcKimlikNo1'),
            'gender' => $request->get('Cinsiyet') == 1 ? 'female' : 'male',
        ];

        if ($request->has('KoltukNo2')) {
            $passengersData['KoltukNo2'] = $request->get('KoltukNo2');
            $passengersData['Adi2'] = $request->get('Adi2');
            $passengersData['Soyadi2'] = $request->get('Soyadi2');
            $passengersData['Cinsiyet2'] = $request->get('Cinsiyet2');
            $passengersData['TcKimlikNo2'] = $request->get('TcKimlikNo2');

            $passengers[] = [
                'name' => $request->get('Adi2'),
                'surname' => $request->get('Soyadi2'),
                'tcNo' => $request->get('TcKimlikNo2'),
                'gender' => $request->get('Cinsiyet2') == 1 ? 'female' : 'male',
            ];
        }

        if ($request->has('KoltukNo3')) {

            $passengersData['KoltukNo3'] = $request->get('KoltukNo3');
            $passengersData['Adi3'] = $request->get('Adi3');
            $passengersData['Soyadi3'] = $request->get('Soyadi3');
            $passengersData['TcKimlikNo3'] = $request->get('TcKimlikNo3');
            $passengersData['Cinsiyet3'] = $request->get('Cinsiyet3');

            $passengers[] = [
                'name' => $request->get('Adi3'),
                'surname' => $request->get('Soyadi3'),
                'tcNo' => $request->get('TcKimlikNo3'),
                'gender' => $request->get('Cinsiyet3') == 1 ? 'female' : 'male',
            ];
        }

        if ($request->has('KoltukNo4')) {
            $passengersData['KoltukNo4'] = $request->get('KoltukNo4');
            $passengersData['Adi4'] = $request->get('Adi4');
            $passengersData['Soyadi4'] = $request->get('Soyadi4');
            $passengersData['Cinsiyet4'] = $request->get('Cinsiyet4');
            $passengersData['TcKimlikNo4'] = $request->get('TcKimlikNo4');

            $passengers[] = [
                'name' => $request->get('Adi4'),
                'surname' => $request->get('Soyadi4'),
                'tcNo' => $request->get('TcKimlikNo4'),
                'gender' => $request->get('Cinsiyet4') == 1 ? 'female' : 'male',
            ];
        }
        $cardInfo = [];
        if (empty($this->paymentSendCard)) { // @TODO: platform posu ile ödeme alır.
            $cardInfo = [
                'OnOdemeKullan' => '1',
                'OnOdemeTutar' => $request->get('ToplamBiletFiyati')
            ];
        } else { // @TODO: otobüs firması posu ile ödeme alır.
            $cardInfo = [
                "KrediKartNo" => $request->get('KrediKartNo'),
                "KrediKartSahip" => $request->get('KrediKartSahip'),
                "KrediKartGecerlilikTarihi" => $request->get('KrediKartGecerlilikTarihi'),
                "KrediKartCCV2" => $request->get('KrediKartCCV2'),
                'OnOdemeKullan' => '1',
                'OnOdemeTutar' => $request->get('ToplamBiletFiyati'),
                // "AcikPnrNo" => $this->paymentSendCard['PNR']??'',
                //"AcikPnrSoyad" => '',
                // "AcikTutar" => $request->get('ToplamBiletFiyati'),
                //"RezervePnrNo" => '',
            ];
        }

        $foot = [
            'TelefonNo' => $request->get('TelefonNo'),
            'Cinsiyet' => $request->get('Cinsiyet'), // 1 bayan - 2 erkek
            'ToplamBiletFiyati' => $request->get('ToplamBiletFiyati'),
            'YolcuSayisi' => $request->get('YolcuSayisi'),
            'BiletSeriNo' => $request->get('BiletSeriNo'),
            'OdemeSekli' => $request->get('OdemeSekli'),
            'FirmaAciklama' => $request->get('FirmaAciklama'),
            'HatirlaticiNot' => $request->get('HatirlaticiNot'),
            'SeyahatTipi' => $request->get('SeyahatTipi'), // 0 ?
            'WebYolcu' => array_merge([
                'WebUyeNo' => '0',
                'Ip' => '31.206.44.48', // $request->ip(),
                'Email' => $request->get('Email'),
            ], $cardInfo),

        ];

        $formData = array_merge($busData, $passengersData, $foot);


        return [
            'form' => $formData,
            'passengers' => $passengers
        ];
    }

}
