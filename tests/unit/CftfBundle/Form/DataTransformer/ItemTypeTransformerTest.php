<?php

namespace CftfBundle\Form\DataTransformer;

use CftfBundle\Entity\LsDefItemType;
use Doctrine\Common\Persistence\ObjectManager;

class ItemTypeTransformerTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

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
