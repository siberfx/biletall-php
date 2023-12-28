<?php

namespace Siberfx\BiletAll\Helpers;

class BooleanParser
{

    public static function parse(string $binaryString = '', array $valueMap = [])
    {
        $length = strlen($binaryString);

        for ($i = 0; $i < $length; $i++) {
            $currentChar = $binaryString[$i];
            $booleanValue = self::charToBoolean($currentChar);

            $valueMap[$i] = $booleanValue;
        }

        return collect($valueMap)->map(fn($value, $id) => [
            'id' => $id,
            'value' => $value,
        ])
            ->where('value', 1);
    }

    private static function charToBoolean($char): int
    {
        // Assuming '1' is true and '0' is false
        return $char == '1';
    }
}
