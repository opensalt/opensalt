<?php

namespace Tests\Unit\App\Form\DataTransformer;

use App\Entity\Framework\LsDefItemType;
use App\Form\DataTransformer\ItemTypeTransformer;
use Doctrine\ORM\EntityManagerInterface;

class ItemTypeTransformerTest extends \Codeception\Test\Unit
{
    // tests
    public function testTransformWithNewEntity()
    {
        $stub = $this->createMock(EntityManagerInterface::class);

        $transformer = new ItemTypeTransformer($stub, LsDefItemType::class, 'title', 'id');

        $itemType = new LsDefItemType();
        $itemType->setTitle('Testing');

        $transformed = $transformer->transform($itemType);

        $this->assertTrue(is_array($transformed));
        $this->assertEquals(['' => 'Testing'], $transformed);
    }
}
