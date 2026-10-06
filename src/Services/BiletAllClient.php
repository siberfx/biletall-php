<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Services;

use InvalidArgumentException;
use Siberfx\BiletAll\Exceptions\BiletAllException;
use Siberfx\BiletAll\Helpers\XmlToArray;
use Siberfx\Soap\Exceptions\SoapException;
use Siberfx\Soap\SoapWrapper;
use XMLWriter;

/**
 * Sends BiletAll commands through the XmlIslet operation.
 *
 * The SOAP transport is the "biletall" service of siberfx/laravel-soap, so it
 * can be faked in tests with Soap::fake(['biletall.XmlIslet' => ...]).
 */
final class BiletAllClient
{
    public const string SERVICE = 'biletall';

    /**
     * @param  array<string, mixed>  $config  The "biletall" config array.
     */
    public function __construct(
        private readonly SoapWrapper $soap,
        private readonly array $config,
    ) {
        if (! $this->soap->has(self::SERVICE)) {
            $this->soap->add(self::SERVICE, [
                ...($this->config['soap'] ?? []),
                'wsdl' => $this->wsdl(),
            ]);
        }
    }

    /**
     * Run a BiletAll command, e.g. request('Sefer', ['FirmaNo' => 0, ...]).
     *
     * The returned array mirrors the response XML: request('Sefer') gives
     * ['NewDataSet' => ['Table' => [...]]]. An element that occurs once is
     * returned as an associative array, not a list.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     *
     * @throws BiletAllException
     */
    public function request(string $command, array $parameters = []): array
    {
        try {
            $response = $this->soap->call(self::SERVICE.'.XmlIslet', [[
                'xmlIslem' => ['any' => $this->commandXml($command, $parameters)],
                'xmlYetki' => ['any' => $this->credentialsXml()],
            ]]);
        } catch (SoapException $exception) {
            throw BiletAllException::requestFailed($command, $exception);
        }

        try {
            return $this->parse($response);
        } catch (InvalidArgumentException $exception) {
            throw BiletAllException::invalidResponse($command, $exception);
        }
    }

    /**
     * Build the <Command>...</Command> payload. Values are XML-escaped, null
     * becomes an empty element and a list repeats the element per item.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function commandXml(string $command, array $parameters = []): string
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $this->writeElement($writer, $command, $parameters === [] ? null : $parameters);

        return $writer->outputMemory();
    }

    private function credentialsXml(): string
    {
        return $this->commandXml('Kullanici', [
            'Adi' => (string) ($this->config['username'] ?? ''),
            'Sifre' => (string) ($this->config['password'] ?? ''),
        ]);
    }

    private function wsdl(): string
    {
        $environment = ($this->config['sandbox'] ?? false) ? 'test' : 'live';

        return $this->config['wsdl'][$environment]
            ?? throw new InvalidArgumentException("The BiletAll {$environment} WSDL is not configured.");
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     */
    private function writeElements(XMLWriter $writer, array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            if (is_array($value) && $value !== [] && array_is_list($value)) {
                foreach ($value as $item) {
                    $this->writeElement($writer, (string) $name, $item);
                }

                continue;
            }

            $this->writeElement($writer, (string) $name, $value);
        }
    }

    private function writeElement(XMLWriter $writer, string $name, mixed $value): void
    {
        if (preg_match('/^[A-Za-z_][\w.\-]*$/', $name) !== 1) {
            throw new InvalidArgumentException("[{$name}] is not a valid BiletAll parameter name.");
        }

        $writer->startElement($name);

        match (true) {
            $value === null => null,
            is_array($value) => $this->writeElements($writer, $value),
            is_bool($value) => $writer->text($value ? '1' : '0'),
            default => $writer->text((string) $value),
        };

        $writer->endElement();
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(mixed $response): array
    {
        $xml = is_string($response) ? $response : data_get($response, 'XmlIsletResult.any');

        if (! is_string($xml) || trim($xml) === '') {
            return [];
        }

        // The result may hold several sibling elements, so wrap it in a single root.
        $xml = preg_replace('/^\s*<\?xml[^>]*\?>/', '', $xml);
        $result = XmlToArray::convert('<Result>'.$xml.'</Result>');

        return is_array($result) ? $result : [];
    }
}
