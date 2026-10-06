<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Helpers;

use Illuminate\Support\Collection;

class BooleanParser
{
    /**
     * Positions of the "1" flags in a binary string, e.g. "0110" gives
     * [['id' => 1, 'value' => 1], ['id' => 2, 'value' => 1]] keyed by position.
     *
     * @return Collection<int, array{id: int, value: int}>
     */
    public static function parse(string $binaryString = ''): Collection
    {
        return collect(str_split($binaryString))
            ->filter(static fn (string $char): bool => $char === '1')
            ->map(static fn (string $char, int $position): array => ['id' => $position, 'value' => 1]);
    }
}
