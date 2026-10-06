<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Helpers;

class BusSpecHelper
{
    /**
     * Turn an OTipOzellik flag string into the matching entries of biletall.features.
     *
     * Each character is the true (1) / false (0) state of the feature whose
     * "tip" equals its position. When biletall.use_default_feature_flags is
     * on, biletall.default_feature_flags is decoded instead.
     *
     * @return list<array{id: int, title: string, description: string, image: string}>
     */
    public static function handle(mixed $flags = ''): array
    {
        if (! is_string($flags) || $flags === '') {
            return [];
        }

        if (self::useDefaultFlags()) {
            $flags = (string) config('biletall.default_feature_flags', $flags);
        }

        $active = BooleanParser::parse($flags)->pluck('id')->all();

        if ($active === []) {
            return [];
        }

        $imagePath = trim((string) config('biletall.feature_image_path', ''), '/');

        return collect(config('biletall.features', []))
            ->whereIn('tip', $active)
            ->map(static fn (array $feature): array => [
                'id' => (int) $feature['tip'],
                'title' => (string) $feature['tip_aciklama'],
                'description' => (string) $feature['tip_detay'],
                'image' => empty($feature['tip_image'])
                    ? ''
                    : asset(ltrim($imagePath.'/'.$feature['tip_image'], '/')),
            ])
            ->values()
            ->all();
    }

    /**
     * Explicit config wins; when it is null, follow the original "local only" behaviour.
     */
    private static function useDefaultFlags(): bool
    {
        $use = config('biletall.use_default_feature_flags');

        if ($use === null || $use === '') {
            return app()->environment('local');
        }

        return filter_var($use, FILTER_VALIDATE_BOOL);
    }
}
