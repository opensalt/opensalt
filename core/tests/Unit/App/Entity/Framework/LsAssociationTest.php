<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Ramsey\Uuid\Uuid;

class LsAssociationTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsAssociation = new LsAssociation();

        $this->assertInstanceOf(LsAssociation::class, $lsAssociation);
        $this->assertNotNull($lsAssociation->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsAssociation->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsAssociation->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsAssociation = new LsAssociation($identifier);

        $this->assertEquals($identifier, $lsAssociation->getIdentifier());
    }

    public function testSetAndGetLsDocIdentifier()
    {
        $lsAssociation = new LsAssociation();
        $lsDocIdentifier = Uuid::uuid4()->toString();

        $lsAssociation->setLsDocIdentifier($lsDocIdentifier);
        $this->assertEquals($lsDocIdentifier, $lsAssociation->getLsDocIdentifier());
    }

    public function testSetAndGetLsDocUri()
    {
        $lsAssociation = new LsAssociation();
        $lsDocUri = 'https://example.com/doc';

        $lsAssociation->setLsDocUri($lsDocUri);
        $this->assertEquals($lsDocUri, $lsAssociation->getLsDocUri());
    }

    public function testSetAndGetOriginNodeIdentifier()
    {
        $lsAssociation = new LsAssociation();
        $originNodeIdentifier = Uuid::uuid4()->toString();

        $lsAssociation->setOriginNodeIdentifier($originNodeIdentifier);
        $this->assertEquals($originNodeIdentifier, $lsAssociation->getOriginNodeIdentifier());
    }

    public function testSetAndGetOriginNodeUri()
    {
        $lsAssociation = new LsAssociation();
        $originNodeUri = 'https://example.com/item/1';

        $lsAssociation->setOriginNodeUri($originNodeUri);
        $this->assertEquals($originNodeUri, $lsAssociation->getOriginNodeUri());
    }

    public function testSetAndGetOriginNodeTargetType()
    {
        $lsAssociation = new LsAssociation();
        $targetType = 'item';

        $lsAssociation->setOriginNodeTargetType($targetType);
        $this->assertEquals($targetType, $lsAssociation->getOriginNodeTargetType());
    }

    public function testSetAndGetDestinationNodeIdentifier()
    {
        $lsAssociation = new LsAssociation();
        $destinationNodeIdentifier = Uuid::uuid4()->toString();

        $lsAssociation->setDestinationNodeIdentifier($destinationNodeIdentifier);
        $this->assertEquals($destinationNodeIdentifier, $lsAssociation->getDestinationNodeIdentifier());
    }

    public function testSetAndGetDestinationNodeUri()
    {
        $lsAssociation = new LsAssociation();
        $destinationNodeUri = 'https://example.com/item/2';

        $lsAssociation->setDestinationNodeUri($destinationNodeUri);
        $this->assertEquals($destinationNodeUri, $lsAssociation->getDestinationNodeUri());
    }

    public function testSetAndGetDestinationNodeTargetType()
    {
        $lsAssociation = new LsAssociation();
        $targetType = 'item';

        $lsAssociation->setDestinationNodeTargetType($targetType);
        $this->assertEquals($targetType, $lsAssociation->getDestinationNodeTargetType());
    }

    public function testSetAndGetType()
    {
        $lsAssociation = new LsAssociation();
        $type = LsAssociation::CHILD_OF;

        $lsAssociation->setType($type);
        $this->assertEquals($type, $lsAssociation->getType());
    }

    public function testSetTypeWithInvalidType()
    {
        $lsAssociation = new LsAssociation();

        $this->expectException(\InvalidArgumentException::class);
        $lsAssociation->setType('Invalid Type');
    }

    public function testSetAndGetSequenceNumber()
    {
        $lsAssociation = new LsAssociation();

        $lsAssociation->setSequenceNumber(5);
        $this->assertEquals(5, $lsAssociation->getSequenceNumber());

        $lsAssociation->setSequenceNumber('10');
        $this->assertEquals(10, $lsAssociation->getSequenceNumber());

        $lsAssociation->setSequenceNumber(null);
        $this->assertEquals(0, $lsAssociation->getSequenceNumber()); // Default value is 0, not null
    }

    public function testSetSequenceNumberWithInvalidValue()
    {
        $lsAssociation = new LsAssociation();

        $this->expectException(\InvalidArgumentException::class);
        $lsAssociation->setSequenceNumber('abc');
    }

    public function testSetAndGetSubtype()
    {
        $lsAssociation = new LsAssociation();
        $subtype = 'subtype';

        $lsAssociation->setSubtype($subtype);
        $this->assertEquals($subtype, $lsAssociation->getSubtype());
    }

    public function testSetAndGetNotes()
    {
        $lsAssociation = new LsAssociation();
        $notes = 'These are some notes';

        $lsAssociation->setNotes($notes);
        $this->assertEquals($notes, $lsAssociation->getNotes());
    }

    public function testSetAndGetGroup()
    {
        $lsAssociation = new LsAssociation();
        $group = new LsDefAssociationGrouping();

        $lsAssociation->setGroup($group);
        $this->assertEquals($group, $lsAssociation->getGroup());
    }

    public function testSetAndGetLsDoc()
    {
        $lsAssociation = new LsAssociation();
        $lsDoc = new LsDoc();

        $lsAssociation->setLsDoc($lsDoc);
        $this->assertEquals($lsDoc, $lsAssociation->getLsDoc());
    }

    public function testSetAndGetOriginLsDoc()
    {
        $lsAssociation = new LsAssociation();
        $lsDoc = new LsDoc();

        $lsAssociation->setOriginLsDoc($lsDoc);
        $this->assertEquals($lsDoc, $lsAssociation->getOriginLsDoc());
    }

    public function testSetAndGetOriginLsItem()
    {
        $lsAssociation = new LsAssociation();
        $lsItem = new LsItem();

        $lsAssociation->setOriginLsItem($lsItem);
        $this->assertEquals($lsItem, $lsAssociation->getOriginLsItem());
    }

    public function testSetAndGetDestinationLsDoc()
    {
        $lsAssociation = new LsAssociation();
        $lsDoc = new LsDoc();

        $lsAssociation->setDestinationLsDoc($lsDoc);
        $this->assertEquals($lsDoc, $lsAssociation->getDestinationLsDoc());
    }

    public function testSetAndGetDestinationLsItem()
    {
        $lsAssociation = new LsAssociation();
        $lsItem = new LsItem();

        $lsAssociation->setDestinationLsItem($lsItem);
        $this->assertEquals($lsItem, $lsAssociation->getDestinationLsItem());
    }

    public function testSetOriginWithLsDoc()
    {
        $lsAssociation = new LsAssociation();
        $lsDoc = new LsDoc();

        $lsAssociation->setOrigin($lsDoc);
        $this->assertEquals($lsDoc, $lsAssociation->getOriginLsDoc());
    }

    public function testSetOriginWithLsItem()
    {
        $lsAssociation = new LsAssociation();
        $lsItem = new LsItem();

        $lsAssociation->setOrigin($lsItem);
        $this->assertEquals($lsItem, $lsAssociation->getOriginLsItem());
    }

    public function testSetOriginWithString()
    {
        $lsAssociation = new LsAssociation();
        $uri = 'https://example.com/item';

        $lsAssociation->setOrigin($uri);
        $this->assertEquals($uri, $lsAssociation->getOriginNodeUri());
        $this->assertEquals($uri, $lsAssociation->getOriginNodeIdentifier());
    }

    public function testSetDestinationWithLsDoc()
    {
        $lsAssociation = new LsAssociation();
        $lsDoc = new LsDoc();

        $lsAssociation->setDestination($lsDoc);
        $this->assertEquals($lsDoc, $lsAssociation->getDestinationLsDoc());
    }

    public function testSetDestinationWithLsItem()
    {
        $lsAssociation = new LsAssociation();
        $lsItem = new LsItem();

        $lsAssociation->setDestination($lsItem);
        $this->assertEquals($lsItem, $lsAssociation->getDestinationLsItem());
    }

    public function testSetDestinationWithString()
    {
        $lsAssociation = new LsAssociation();
        $uri = 'https://example.com/item';

        $lsAssociation->setDestination($uri);
        $this->assertEquals($uri, $lsAssociation->getDestinationNodeUri());
        $this->assertEquals($uri, $lsAssociation->getDestinationNodeIdentifier());
    }

    public function testGetOrigin()
    {
        $lsAssociation = new LsAssociation();

        // Test with LsDoc
        $lsDoc = new LsDoc();
        $lsAssociation->setOriginLsDoc($lsDoc);
        $this->assertEquals($lsDoc, $lsAssociation->getOrigin());

        // Test with LsItem
        $lsItem = new LsItem();
        $lsAssociation->setOriginLsItem($lsItem);
        $lsAssociation->setOriginLsDoc(null);
        $this->assertEquals($lsItem, $lsAssociation->getOrigin());

        // Test with URI
        $lsAssociation->setOriginLsItem(null);
        $lsAssociation->setOriginNodeUri('https://example.com/item');
        $this->assertEquals('https://example.com/item', $lsAssociation->getOrigin());
    }

    public function testGetDestination()
    {
        $lsAssociation = new LsAssociation();

        // Test with LsDoc
        $lsDoc = new LsDoc();
        $lsAssociation->setDestinationLsDoc($lsDoc);
        $this->assertEquals($lsDoc, $lsAssociation->getDestination());

        // Test with LsItem
        $lsItem = new LsItem();
        $lsAssociation->setDestinationLsItem($lsItem);
        $lsAssociation->setDestinationLsDoc(null);
        $this->assertEquals($lsItem, $lsAssociation->getDestination());

        // Test with URI
        $lsAssociation->setDestinationLsItem(null);
        $lsAssociation->setDestinationNodeUri('https://example.com/item');
        $this->assertEquals('https://example.com/item', $lsAssociation->getDestination());
    }

    public function testGetNormalizedType()
    {
        $lsAssociation = new LsAssociation();

        $lsAssociation->setType(LsAssociation::CHILD_OF);
        $this->assertEquals('isChildOf', $lsAssociation->getNormalizedType());

        $lsAssociation->setType(LsAssociation::EXACT_MATCH_OF);
        $this->assertEquals('exactMatchOf', $lsAssociation->getNormalizedType());
    }

    public function testCoerceType()
    {
        $lsAssociation = new LsAssociation();

        $this->assertEquals(LsAssociation::CHILD_OF, $lsAssociation->coerceType('is child of'));
        $this->assertEquals(LsAssociation::EXACT_MATCH_OF, $lsAssociation->coerceType('exact match of'));
        $this->assertNull($lsAssociation->coerceType('invalid type'));
    }

    public function testAllTypes()
    {
        $types = LsAssociation::allTypes();

        $this->assertIsArray($types);
        $this->assertContains(LsAssociation::CHILD_OF, $types);
        $this->assertContains(LsAssociation::EXACT_MATCH_OF, $types);
        $this->assertContains(LsAssociation::RELATED_TO, $types);
    }

    public function testTypeChoiceList()
    {
        $choiceList = LsAssociation::typeChoiceList();

        $this->assertIsArray($choiceList);
        $this->assertArrayHasKey(LsAssociation::RELATED_TO, $choiceList);
        $this->assertArrayHasKey(LsAssociation::EXACT_MATCH_OF, $choiceList);
    }

    public function testInverseName()
    {
        $this->assertEquals(LsAssociation::INVERSE_CHILD_OF, LsAssociation::inverseName(LsAssociation::CHILD_OF));
        $this->assertEquals(LsAssociation::INVERSE_EXACT_MATCH_OF, LsAssociation::inverseName(LsAssociation::EXACT_MATCH_OF));
        $this->assertNull(LsAssociation::inverseName('invalid type'));
    }

    public function testAllTypesForImportFromCSV()
    {
        $types = LsAssociation::allTypesForImportFromCSV();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('isPartOf', $types);
        $this->assertArrayHasKey('exemplar', $types);
    }

    public function testCanEdit()
    {
        $lsAssociation = new LsAssociation();
        $lsDoc = new LsDoc();

        $lsAssociation->setLsDoc($lsDoc);
        $this->assertTrue($lsAssociation->canEdit());
    }

    public function testToString()
    {
        $lsAssociation = new LsAssociation();

        $this->assertEquals($lsAssociation->getUri(), (string) $lsAssociation);
    }

    public function testConstants()
    {
        $this->assertEquals('isChildOf', LsAssociation::CHILD_OF);
        $this->assertEquals('exactMatchOf', LsAssociation::EXACT_MATCH_OF);
        $this->assertEquals('isRelatedTo', LsAssociation::RELATED_TO);
        $this->assertEquals('isPartOf', LsAssociation::PART_OF);
        $this->assertEquals('replacedBy', LsAssociation::REPLACED_BY);
        $this->assertEquals('precedes', LsAssociation::PRECEDES);
        $this->assertEquals('hasSkillLevel', LsAssociation::SKILL_LEVEL);
        $this->assertEquals('isPeerOf', LsAssociation::IS_PEER_OF);
        $this->assertEquals('isTranslationOf', LsAssociation::IS_TRANSLATION_OF);
        $this->assertEquals('exemplar', LsAssociation::EXEMPLAR);
    }

    public function testTypePropertyHasNotBlankConstraint(): void
    {
        $reflection = new \ReflectionClass(LsAssociation::class);
        $property = $reflection->getProperty('type');
        $attributes = $property->getAttributes(\Symfony\Component\Validator\Constraints\NotBlank::class);

        $this->assertNotEmpty($attributes, 'The $type property should have a NotBlank constraint');
    }

    public function testClassHasCallbackConstraintForTypeValidation(): void
    {
        $reflection = new \ReflectionClass(LsAssociation::class);
        $attributes = $reflection->getAttributes(\Symfony\Component\Validator\Constraints\Callback::class);

        $this->assertNotEmpty($attributes, 'The LsAssociation class should have a Callback constraint');
    }

    public function testBaseTypesContainsAllExpectedTypes(): void
    {
        $expectedTypes = [
            'isChildOf', 'isRelatedTo', 'exactMatchOf', 'isPartOf',
            'replacedBy', 'precedes', 'hasSkillLevel', 'isPeerOf',
            'isTranslationOf', 'exemplar',
        ];

        foreach ($expectedTypes as $type) {
            $this->assertContains($type, LsAssociation::BASE_TYPES, "BASE_TYPES should contain $type");
        }
    }

    public function testValidateTypeAcceptsBaseTypes(): void
    {
        $association = new LsAssociation();
        $association->setType(LsAssociation::CHILD_OF);
        $context = $this->createMock(\Symfony\Component\Validator\Context\ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        LsAssociation::validateType($association, $context);
    }

    public function testValidateTypeAcceptsExtTypes(): void
    {
        $association = new LsAssociation();
        $association->setType('ext:customType');
        $context = $this->createMock(\Symfony\Component\Validator\Context\ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        LsAssociation::validateType($association, $context);
    }

    public function testValidateTypeRejectsInvalidType(): void
    {
        $association = new LsAssociation();
        $association->setType('Invalid Type');

        $violationBuilder = $this->createMock(\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())->method('setParameter')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(\Symfony\Component\Validator\Context\ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn($violationBuilder);

        LsAssociation::validateType($association, $context);
    }

    public function testValidateTypeAllowsNullType(): void
    {
        $association = new LsAssociation();
        $context = $this->createMock(\Symfony\Component\Validator\Context\ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        LsAssociation::validateType($association, $context);
    }

    /**
     * @param string $uri Uri to be split
     * @param array $expectedResult What we expect the result to be
     *
     * @dataProvider providerTestSplitDestinationDataUri
     */
    public function testSplitDestinationDataUri($uri, $expectedResult)
    {
        $assoc = new LsAssociation();
        $assoc->setDestinationNodeUri($uri);

        $value = $assoc->splitDestinationDataUri();

        foreach ($expectedResult as $field => $fieldValue) {
            $this->assertEquals($fieldValue, $value[$field], "Value for {$field} '{$value[$field]}' does not match expected '{$fieldValue}''.");
        }
        $this->assertEquals(
            count($expectedResult),
            count($value),
            'Field counts do not match - '
                .implode(',', array_keys($expectedResult))
                .' expected, but found '
                .implode(',', array_keys($value))
        );
    }

    public function providerTestSplitDestinationDataUri()
    {
        return [
            ['Not Split', ['value' => 'Not Split']],
            ['data:text/x-ref-unresolved;base64,TS5TSFQuMQ==', [
                'value' => 'M.SHT.1',
                'textType' => 'ref-unresolved',
                'base64' => true,
            ]],
            ['data:text/x-ref-unresolved;base64,Q0NTUy5NQVRILkNPTlRFTlQuSy5BLjE=', [
                'value' => 'CCSS.MATH.CONTENT.K.A.1',
                'textType' => 'ref-unresolved',
                'base64' => true,
            ]],
            ['data:text/x-ref-unresolved;base64,VC5TLjE=', [
                'value' => 'T.S.1',
                'textType' => 'ref-unresolved',
                'base64' => true,
            ]],
            ['data:text/x-ref;src=test,T.2.4', [
                'textType' => 'ref',
                'src' => 'test',
                'value' => 'T.2.4',
            ]],
            ['data:text/x-ref,T.2.5', [
                'textType' => 'ref',
                'value' => 'T.2.5',
            ]],
        ];
    }
}
