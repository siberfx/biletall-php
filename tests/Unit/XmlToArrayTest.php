<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Siberfx\BiletAll\Helpers\XmlToArray;

class XmlToArrayTest extends TestCase
{
    public function test_single_elements_collapse_and_repeated_ones_become_lists(): void
    {
        $result = XmlToArray::convert(
            '<NewDataSet><Table><ID>1</ID><Ad>Ankara</Ad></Table><Table><ID>2</ID><Ad>İzmir</Ad></Table><Bos/></NewDataSet>'
        );

        $this->assertSame([
            'Table' => [['ID' => '1', 'Ad' => 'Ankara'], ['ID' => '2', 'Ad' => 'İzmir']],
            'Bos' => [],
        ], $result);
    }

    public function test_attributes_and_content(): void
    {
        $result = XmlToArray::convert('<a><b id="7">metin</b><c x="1"><d>0</d></c></a>');

        $this->assertSame([
            'b' => ['@content' => 'metin', '@attributes' => ['id' => '7']],
            'c' => ['d' => '0', '@attributes' => ['x' => '1']],
        ], $result);
    }

    public function test_output_root(): void
    {
        $this->assertSame(['b' => 'x', '@root' => 'a'], XmlToArray::convert('<a><b>x</b></a>', true));
        $this->assertSame(['b' => 'x'], xml_to_array('<a><b>x</b></a>'));
    }

    public function test_invalid_xml_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        XmlToArray::convert('<a><b></a>');
    }
}
