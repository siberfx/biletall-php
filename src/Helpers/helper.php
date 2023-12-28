<?php

function xml_to_array($xml, $outputRoot = false): array|string
{
    return \Siberfx\BiletAll\Helpers\XmlToArray::convert($xml, $outputRoot);
}
