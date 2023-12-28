<?php

namespace Siberfx\BiletAll\Helpers;

class BusSpecHelper
{

    public static function handle(string $string = '')
    {
        $defaultSet = '11111111001000100000000000000000000000000000000000';

        if (empty($string)) {
            return [];
        }

        if (app()->environment('local')) {
            $string = str_limit($defaultSet, 23);
        }


        // @todo test data for 23 characters string

        $ids = BooleanParser::parse(str_limit($string, 23))->pluck('id')->toArray();

        if (empty($ids)) {
            return [];
        }

        return collect(config('biletall.set'))->whereIn('tip', $ids)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'image' => !empty($item->image) ? asset($item->image) : ''
            ];
        });

    }


}
