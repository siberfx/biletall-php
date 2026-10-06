<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Tests\Feature;

use Siberfx\BiletAll\Exceptions\BiletAllException;
use Siberfx\BiletAll\Services\BiletAllClient;
use Siberfx\BiletAll\Tests\TestCase;
use Siberfx\Soap\Facades\Soap;
use Siberfx\Soap\Testing\RecordedCall;
use SoapFault;

class BiletAllClientTest extends TestCase
{
    public function test_it_registers_the_soap_service_for_the_selected_environment(): void
    {
        config(['biletall.sandbox' => true]);

        $this->app->make(BiletAllClient::class);

        $service = Soap::service(BiletAllClient::SERVICE);

        $this->assertSame('http://62.248.56.228/WSTEST/Service.asmx?wsdl', $service->wsdl);
        $this->assertSame(SOAP_1_2, $service->soapVersion);
    }

    public function test_it_sends_escaped_command_and_credentials_and_parses_the_result(): void
    {
        Soap::fake([
            'biletall.XmlIslet' => $this->fakeResult('<NewDataSet><Table><ID>5</ID></Table></NewDataSet>'),
        ]);

        $result = $this->app->make(BiletAllClient::class)->request('Sefer', [
            'Soyad' => 'O<Brien & Co',
            'Bos' => null,
            'Aktif' => true,
            'WebYolcu' => ['Email' => 'a@b.c'],
            'Koltuklar' => [['Koltuk' => 1], ['Koltuk' => 2]],
        ]);

        $this->assertSame(['NewDataSet' => ['Table' => ['ID' => '5']]], $result);

        Soap::assertCalled('biletall.XmlIslet', function (RecordedCall $call): bool {
            $this->assertSame(
                '<Sefer><Soyad>O&lt;Brien &amp; Co</Soyad><Bos/><Aktif>1</Aktif><WebYolcu><Email>a@b.c</Email></WebYolcu>'
                .'<Koltuklar><Koltuk>1</Koltuk></Koltuklar><Koltuklar><Koltuk>2</Koltuk></Koltuklar></Sefer>',
                $call->argument('0.xmlIslem.any'),
            );
            $this->assertSame(
                '<Kullanici><Adi>demo-user</Adi><Sifre>p&lt;ss&amp;</Sifre></Kullanici>',
                $call->argument('0.xmlYetki.any'),
            );

            return true;
        });
    }

    public function test_a_command_without_parameters_is_an_empty_element(): void
    {
        $this->assertSame(
            '<KaraNoktaGetirKomut/>',
            $this->app->make(BiletAllClient::class)->commandXml('KaraNoktaGetirKomut'),
        );
    }

    public function test_empty_result_gives_an_empty_array(): void
    {
        Soap::fake(['biletall.XmlIslet' => $this->fakeResult('')]);

        $this->assertSame([], $this->app->make(BiletAllClient::class)->request('Sefer'));
    }

    public function test_soap_faults_are_wrapped(): void
    {
        Soap::fake(['biletall.XmlIslet' => new SoapFault('Server', 'boom')]);

        try {
            $this->app->make(BiletAllClient::class)->request('Sefer');
            $this->fail('Expected a BiletAllException.');
        } catch (BiletAllException $exception) {
            $this->assertSame('Sefer', $exception->command);
            $this->assertStringContainsString('boom', $exception->getMessage());
        }
    }

    public function test_unreadable_xml_is_wrapped(): void
    {
        Soap::fake(['biletall.XmlIslet' => $this->fakeResult('<NewDataSet><Table>')]);

        $this->expectException(BiletAllException::class);

        $this->app->make(BiletAllClient::class)->request('Sefer');
    }
}
