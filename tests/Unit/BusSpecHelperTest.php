<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Tests\Unit;

use Siberfx\BiletAll\Helpers\BooleanParser;
use Siberfx\BiletAll\Helpers\BusSpecHelper;
use Siberfx\BiletAll\Tests\TestCase;

class BusSpecHelperTest extends TestCase
{
    public function test_boolean_parser_returns_positions_of_ones(): void
    {
        $this->assertSame([1, 3], BooleanParser::parse('0101')->pluck('id')->values()->all());
        $this->assertTrue(BooleanParser::parse('')->isEmpty());
    }

    public function test_flags_are_mapped_to_configured_features(): void
    {
        $features = BusSpecHelper::handle('1001'.str_repeat('0', 46));

        $this->assertSame([0, 3], array_column($features, 'id'));
        $this->assertSame('İnternet', $features[0]['title']);
        $this->assertSame('Araçta Tuvalet Bulunmaktadır.', $features[1]['description']);
        $this->assertStringEndsWith('images/bus-features/WC.gif', $features[1]['image']);
    }

    public function test_default_flags_replace_the_soap_value_when_enabled(): void
    {
        config(['biletall.use_default_feature_flags' => true]);

        // 11111111001000100… → tip 0-7, 10 and 14
        $this->assertSame(
            [0, 1, 2, 3, 4, 5, 6, 7, 10, 14],
            array_column(BusSpecHelper::handle('0001'), 'id'),
        );
    }

    public function test_default_flags_follow_the_local_environment_when_not_configured(): void
    {
        config(['biletall.use_default_feature_flags' => null]);

        $this->assertSame([3], array_column(BusSpecHelper::handle('0001'), 'id'));

        $this->app['env'] = 'local';

        $this->assertCount(10, BusSpecHelper::handle('0001'));
    }

    public function test_empty_or_non_string_flags_give_no_features(): void
    {
        $this->assertSame([], BusSpecHelper::handle(''));
        $this->assertSame([], BusSpecHelper::handle([]));
        $this->assertSame([], BusSpecHelper::handle('0000'));
    }
}
