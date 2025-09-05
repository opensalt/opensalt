<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsDocAttribute;

class LsDocAttributeTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDoc = new LsDoc();
        $attribute = 'customAttribute';
        $value = 'customValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $value);

        $this->assertInstanceOf(LsDocAttribute::class, $lsDocAttribute);
        $this->assertEquals($lsDoc, $lsDocAttribute->getLsDoc());
        $this->assertEquals($attribute, $lsDocAttribute->getAttribute());
        $this->assertEquals($value, $lsDocAttribute->getValue());
    }

    public function testConstructorWithNullValue()
    {
        $lsDoc = new LsDoc();
        $attribute = 'nullableAttribute';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, null);

        $this->assertNull($lsDocAttribute->getValue());
    }

    public function testGetLsDoc()
    {
        $lsDoc = new LsDoc();
        $attribute = 'testAttribute';
        $value = 'testValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $value);

        $this->assertEquals($lsDoc, $lsDocAttribute->getLsDoc());
    }

    public function testSetAndGetAttribute()
    {
        $lsDoc = new LsDoc();
        $attribute = 'initialAttribute';
        $value = 'initialValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $value);

        $newAttribute = 'updatedAttribute';
        $lsDocAttribute->setAttribute($newAttribute);

        $this->assertEquals($newAttribute, $lsDocAttribute->getAttribute());
    }

    public function testSetAndGetValue()
    {
        $lsDoc = new LsDoc();
        $attribute = 'testAttribute';
        $value = 'initialValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $value);

        $newValue = 'updatedValue';
        $lsDocAttribute->setValue($newValue);

        $this->assertEquals($newValue, $lsDocAttribute->getValue());
    }

    public function testSetValueToNull()
    {
        $lsDoc = new LsDoc();
        $attribute = 'testAttribute';
        $value = 'initialValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $value);
        $lsDocAttribute->setValue(null);

        $this->assertNull($lsDocAttribute->getValue());
    }

    public function testEmptyAttribute()
    {
        $lsDoc = new LsDoc();
        $attribute = '';
        $value = 'someValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $value);

        $this->assertEquals('', $lsDocAttribute->getAttribute());
    }

    public function testEmptyValue()
    {
        $lsDoc = new LsDoc();
        $attribute = 'testAttribute';
        $value = '';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $value);

        $this->assertEquals('', $lsDocAttribute->getValue());
    }

    public function testLongAttributeName()
    {
        $lsDoc = new LsDoc();
        $longAttribute = str_repeat('very_long_attribute_name_', 5);
        $value = 'testValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $longAttribute, $value);

        $this->assertEquals($longAttribute, $lsDocAttribute->getAttribute());
    }

    public function testLongValue()
    {
        $lsDoc = new LsDoc();
        $attribute = 'longValueAttribute';
        $longValue = str_repeat('This is a very long value that should be stored properly. ', 10);

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $longValue);

        $this->assertEquals($longValue, $lsDocAttribute->getValue());
    }

    public function testSpecialCharactersInAttribute()
    {
        $lsDoc = new LsDoc();
        $specialAttribute = 'attribute-with_special.chars_and:numbers123';
        $value = 'testValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $specialAttribute, $value);

        $this->assertEquals($specialAttribute, $lsDocAttribute->getAttribute());
    }

    public function testSpecialCharactersInValue()
    {
        $lsDoc = new LsDoc();
        $attribute = 'specialValueAttribute';
        $specialValue = 'Value with special chars: @#$%^&*()_+{}|:<>?[]\;\'",./';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $specialValue);

        $this->assertEquals($specialValue, $lsDocAttribute->getValue());
    }

    public function testUnicodeCharacters()
    {
        $lsDoc = new LsDoc();
        $attribute = 'unicodeAttribute';
        $unicodeValue = 'Unicode value: Üñíçødé 中文 العربية';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $unicodeValue);

        $this->assertEquals($unicodeValue, $lsDocAttribute->getValue());
    }

    public function testIsGradeLevelsConstant()
    {
        $this->assertEquals('isGradeLevels', LsDocAttribute::IS_GRADE_LEVELS);
    }

    public function testGradeLevelsAttribute()
    {
        $lsDoc = new LsDoc();
        $value = 'grades 1-5';

        $lsDocAttribute = new LsDocAttribute($lsDoc, LsDocAttribute::IS_GRADE_LEVELS, $value);

        $this->assertEquals(LsDocAttribute::IS_GRADE_LEVELS, $lsDocAttribute->getAttribute());
        $this->assertEquals($value, $lsDocAttribute->getValue());
    }

    public function testDataIntegrity()
    {
        $lsDoc = new LsDoc();
        $attribute = 'testAttribute';
        $value = 'testValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, $value);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($lsDoc, $lsDocAttribute->getLsDoc());
        $this->assertEquals($attribute, $lsDocAttribute->getAttribute());
        $this->assertEquals($value, $lsDocAttribute->getValue());
    }

    public function testMultipleAttributesForSameDoc()
    {
        $lsDoc = new LsDoc();

        $attribute1 = new LsDocAttribute($lsDoc, 'attr1', 'value1');
        $attribute2 = new LsDocAttribute($lsDoc, 'attr2', 'value2');
        $attribute3 = new LsDocAttribute($lsDoc, 'attr3', 'value3');

        $this->assertEquals($lsDoc, $attribute1->getLsDoc());
        $this->assertEquals($lsDoc, $attribute2->getLsDoc());
        $this->assertEquals($lsDoc, $attribute3->getLsDoc());

        $this->assertEquals('attr1', $attribute1->getAttribute());
        $this->assertEquals('attr2', $attribute2->getAttribute());
        $this->assertEquals('attr3', $attribute3->getAttribute());
    }

    public function testAttributeModification()
    {
        $lsDoc = new LsDoc();
        $originalAttribute = 'originalAttr';
        $originalValue = 'originalValue';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $originalAttribute, $originalValue);

        // Modify attribute and value
        $newAttribute = 'modifiedAttr';
        $newValue = 'modifiedValue';

        $lsDocAttribute->setAttribute($newAttribute);
        $lsDocAttribute->setValue($newValue);

        $this->assertEquals($newAttribute, $lsDocAttribute->getAttribute());
        $this->assertEquals($newValue, $lsDocAttribute->getValue());
    }

    public function testNullValueHandling()
    {
        $lsDoc = new LsDoc();
        $attribute = 'nullableAttr';

        $lsDocAttribute = new LsDocAttribute($lsDoc, $attribute, null);
        $this->assertNull($lsDocAttribute->getValue());

        $lsDocAttribute->setValue('not null');
        $this->assertEquals('not null', $lsDocAttribute->getValue());

        $lsDocAttribute->setValue(null);
        $this->assertNull($lsDocAttribute->getValue());
    }
}
