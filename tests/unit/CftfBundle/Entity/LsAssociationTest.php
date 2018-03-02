<?php

namespace CftfBundle\Entity;

use App\Entity\Framework\LsAssociation;

class LsAssociationTest extends \Codeception\Test\Unit
{
    // tests

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
