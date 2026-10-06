<?php

/**
 * Test your BiletAll integration without calling the real service.
 *
 * Place in tests/Feature of your app.
 */

namespace Tests\Feature;

use Siberfx\Soap\Facades\Soap;
use Siberfx\Soap\Testing\RecordedCall;
use SoapFault;
use Tests\TestCase;

class BusSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Fail loudly when a call has no stub.
        Soap::preventStrayCalls();
    }

    /**
     * \SoapClient returns the XmlIslet result as XmlIsletResult->any.
     */
    private function biletAllReturns(string $xml): void
    {
        Soap::fake([
            'biletall.XmlIslet' => (object) ['XmlIsletResult' => (object) ['any' => $xml]],
        ]);
    }

    public function test_trips_are_listed(): void
    {
        $this->biletAllReturns(
            '<NewDataSet>'
            .'<Table><ID>1</ID><FirmaAdi>Demo Turizm</FirmaAdi><BiletFiyatiInternet>750</BiletFiyatiInternet></Table>'
            .'<Table><ID>2</ID><FirmaAdi>Örnek Seyahat</FirmaAdi><BiletFiyatiInternet>690</BiletFiyatiInternet></Table>'
            .'</NewDataSet>'
        );

        $this->postJson('/bus/sefer-ara', ['KalkisNoktaID' => 738, 'VarisNoktaID' => 84, 'Tarih' => '2026-12-06'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.FirmaAdi', 'Örnek Seyahat');

        // Inspect the XML command that would have been sent.
        Soap::assertCalled('biletall.XmlIslet', fn (RecordedCall $call): bool => str_contains(
            $call->argument('0.xmlIslem.any'),
            '<KalkisNoktaID>738</KalkisNoktaID>',
        ));
    }

    public function test_service_outage_returns_bad_gateway(): void
    {
        Soap::fake(['biletall.XmlIslet' => new SoapFault('Server', 'Service unavailable')]);

        $this->postJson('/bus/sefer-ara', ['KalkisNoktaID' => 738, 'VarisNoktaID' => 84, 'Tarih' => '2026-12-06'])
            ->assertStatus(502)
            ->assertJsonPath('success', false);
    }
}
