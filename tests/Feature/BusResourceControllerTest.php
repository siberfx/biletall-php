<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Tests\Feature;

use Siberfx\BiletAll\Tests\TestCase;
use Siberfx\Soap\Facades\Soap;
use Siberfx\Soap\Testing\RecordedCall;
use SoapFault;

class BusResourceControllerTest extends TestCase
{
    private const string LOCATIONS = '<NewDataSet>'
        .'<Table><ID>1</ID><Ad>Ankara Otogarı</Ad><SehirAdi>Ankara</SehirAdi></Table>'
        .'<Table><ID>2</ID><Ad>İzmir Otogarı</Ad><SehirAdi>İzmir</SehirAdi></Table>'
        .'</NewDataSet>';

    public function test_locations_are_listed_and_cached(): void
    {
        Soap::fake(['biletall.XmlIslet' => $this->fakeResult(self::LOCATIONS)]);

        $this->getJson('/bus/kara-noktasi-getir')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $this->getJson('/bus/kara-noktasi-bul?term=ank')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ID', '1');

        Soap::assertCalledTimes('biletall.XmlIslet', 1);
    }

    public function test_location_search_needs_a_term(): void
    {
        $this->getJson('/bus/kara-noktasi-bul?term=a')->assertUnprocessable();

        Soap::assertNothingCalled();
    }

    public function test_sefer_search_validates_input(): void
    {
        $this->postJson('/bus/sefer-ara', ['KalkisNoktaID' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['VarisNoktaID', 'Tarih']);

        Soap::assertNothingCalled();
    }

    public function test_a_single_sefer_is_returned_as_a_list_with_features(): void
    {
        Soap::fake(['biletall.XmlIslet' => $this->fakeResult(
            '<NewDataSet><Table><ID>9</ID><FirmaAdi>Demo Turizm</FirmaAdi><OTipOzellik>0001</OTipOzellik></Table></NewDataSet>'
        )]);

        $this->postJson('/bus/sefer-ara', ['KalkisNoktaID' => 738, 'VarisNoktaID' => 84, 'Tarih' => '2026-10-10'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.FirmaAdi', 'Demo Turizm')
            ->assertJsonPath('data.0.Saat', null)
            ->assertJsonPath('data.0.Ozellikler.0.title', 'WC');

        Soap::assertCalled('biletall.XmlIslet', fn (RecordedCall $call): bool => str_starts_with(
            $call->argument('0.xmlIslem.any'),
            '<Sefer><FirmaNo>0</FirmaNo><KalkisNoktaID>738</KalkisNoktaID>',
        ));
    }

    public function test_no_sefer_found(): void
    {
        Soap::fake(['biletall.XmlIslet' => $this->fakeResult('<NewDataSet/>')]);

        $this->postJson('/bus/sefer-ara', ['KalkisNoktaID' => 1, 'VarisNoktaID' => 2, 'Tarih' => '2026-10-10'])
            ->assertOk()
            ->assertExactJson(['success' => false, 'data' => [], 'message' => 'Hiç bir kayıt bulunamadı']);
    }

    public function test_soap_failures_render_as_bad_gateway(): void
    {
        Soap::fake(['biletall.XmlIslet' => new SoapFault('Server', 'down')]);

        $this->postJson('/bus/sefer-ara', ['KalkisNoktaID' => 1, 'VarisNoktaID' => 2, 'Tarih' => '2026-10-10'])
            ->assertStatus(502)
            ->assertJsonPath('success', false);
    }

    public function test_sale_validates_optional_passengers(): void
    {
        $sale = [
            'Email' => 'yolcu@example.com', 'FirmaNo' => 37, 'KalkisNoktaID' => 738, 'VarisNoktaID' => 84,
            'Tarih' => '2026-10-10', 'Saat' => '2026-10-10T21:30:00+03:00', 'HatNo' => 6, 'SeferNo' => 15039,
            'KoltukNo1' => 3, 'Adi1' => 'Ayşe', 'Soyadi1' => 'Yılmaz', 'TcKimlikNo1' => '10000000146', 'Cinsiyet1' => 1,
            'KoltukNo2' => 4,
            'ToplamBiletFiyati' => 900, 'YolcuSayisi' => 2, 'BiletSeriNo' => 1, 'OdemeSekli' => 0,
        ];

        $this->postJson('/bus/islem-satis', $sale)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Adi2', 'Soyadi2', 'TcKimlikNo2', 'Cinsiyet2']);

        unset($sale['KoltukNo2']);

        $this->postJson('/bus/islem-satis', $sale)->assertStatus(501);

        Soap::assertNothingCalled();
    }
}
