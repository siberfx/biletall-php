<?php

namespace Siberfx\BiletAll\app\Services;

use SoapClient;

class BiletAllSoapClient
{
    private $client;
    private string $wsdlUrl;
    protected string $userName;
    private string $password;

    public function __construct()
    {
        $this->wsdlUrl = config('biletAll.sandbox')
            ? config('biletAll.test.url')
            : config('biletAll.live.url');
        $this->userName = config('biletAll.sandbox')
            ? config('biletAll.test.username')
            : config('biletAll.live.username');
        $this->password = config('biletAll.sandbox')
            ? config('biletAll.test.password')
            : config('biletAll.live.password');

        $options = [
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => 1,
            'exceptions' => (app()->environment('local')),
            'content-type' => 'text/xml;charset=UTF-8',
            'encoding' => 'UTF-8',
            'soap_version' => SOAP_1_1,
            // "soap_action" => "http://tempuri.org/" . $operation,
            'stream_context' => stream_context_create(
                [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]
            )
        ];

        $this->client = new SoapClient($this->wsdlUrl, $options);

    }

    public function doRequestFor(string $action = 'KaraNoktaGetirKomut', array $params = [], $isDebug = false)
    {
        $parameters = $this->formatParameters($params);

        // SOAP isteği gövdesini oluştur
        $soapRequest = $this->soapSchemaFor($action, $parameters);
        if ($isDebug) {
            print_r($soapRequest);
            dd("son");
        }
        // @todo test returned soap XML data
//        echo $soapRequest;
//        exit;

        // SOAP isteğini yap
        $response = $this->client->__doRequest($soapRequest, $this->wsdlUrl, $action, SOAP_1_2);

        // Yanıtı işleme
        return $this->processSoapResponse($response);
    }

    private function soapSchemaFor(string $action = 'KaraNoktaGetirKomut', string $params = ''): string
    {
        if (!empty($params)) {

            $actionWithParam = '<' . $action . '>';
            $actionWithParam .= $params;
            $actionWithParam .= '</' . $action . '>';
        } else {
            $actionWithParam = '<' . $action . ' />';
        }


        return '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:tem="http://tempuri.org/">
                       <soap:Header/>
                       <soap:Body>
                          <tem:XmlIslet>
                             <!--Optional:-->
                             <tem:xmlIslem>
                                ' . $actionWithParam . '
                             </tem:xmlIslem>
                             <!--Optional:-->
                             <tem:xmlYetki>
                                <Kullanici><Adi>' . $this->userName . '</Adi><Sifre>' . $this->password . '</Sifre></Kullanici>
                             </tem:xmlYetki>
                          </tem:XmlIslet>
                       </soap:Body>
                    </soap:Envelope>';
    }

    public function formatParameters(array $params = []): string
    {
        $result = '';

        foreach ($params as $key => $value) {

            $result .= '<' . $key;

            if (is_null($value)) {

                $result .= ' />';
            } else {
                $result .= '>';
                if (is_array($value)) {
                    $result .= $this->formatParameters($value);
                } else {
                    $result .= $value;
                }

                $result .= '</' . $key . '>';
            }

        }

        return $result;

    }

    private function processSoapResponse(string $response = '')
    {
        if (empty($response)) {
            return '';
        }

        $var = xml_to_array($response, true);


        return json_decode(json_encode($var), true)['soap:Body']['XmlIsletResponse']['XmlIsletResult'];
    }

}
