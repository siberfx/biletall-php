<?php

declare(strict_types=1);

use Siberfx\BiletAll\Helpers\XmlToArray;

if (! function_exists('xml_to_array')) {
    /**
     * @return array<string, mixed>|string
     */
    function xml_to_array(string $xml, bool $outputRoot = false): array|string
    {
        return XmlToArray::convert($xml, $outputRoot);
    }
}
