<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Service\EntityIdentifierIndex;
use App\Entity\Framework\LsItem;
use PHPUnit\Framework\TestCase;

final class EntityIdentifierIndexTest extends TestCase
{
    public function testFromLsItemIndexesBuiltInAndExtensionIdentifiers(): void
    {
        $item = $this->createMock(LsItem::class);
        $item->method('getIdentifier')->willReturn('item-1');
        $item->method('getUri')->willReturn('local:item-1');
        $item->method('getHumanCodingScheme')->willReturn('MATH 10');
        $item->method('getExtensions')->willReturn([
            'coci:courseId' => '12345',
            'ignored' => ['nested' => 'value'],
        ]);

        $index = new EntityIdentifierIndex();
        $known = $index->fromLsItem($item);

        $this->assertSame(['item-1'], $known['identifier']);
        $this->assertSame(['local:item-1'], $known['uri']);
        $this->assertSame(['MATH 10'], $known['courseCode']);
        $this->assertSame(['12345'], $known['coci:courseId']);
        $this->assertArrayNotHasKey('ignored', $known);
    }
}
