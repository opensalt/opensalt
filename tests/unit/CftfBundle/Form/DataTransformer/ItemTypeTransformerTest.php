<?php

namespace CftfBundle\Form\DataTransformer;

use App\Entity\Framework\LsDefItemType;
use App\Form\DataTransformer\ItemTypeTransformer;
use Doctrine\Common\Persistence\ObjectManager;

class ItemTypeTransformerTest extends \Codeception\Test\Unit
{
    // tests
    public function testTransformWithNewEntity()
    {
        $stub = $this->createMock(ObjectManager::class);

        $transformer = new ItemTypeTransformer($stub, LsDefItemType::class, 'title', 'id');

        $itemType = new LsDefItemType();
        $itemType->setTitle('Testing');

        $transformed = $transformer->transform($itemType);

        $this->assertTrue(is_array($transformed));
        $this->assertEquals(['' => 'Testing'], $transformed);
    }
}
