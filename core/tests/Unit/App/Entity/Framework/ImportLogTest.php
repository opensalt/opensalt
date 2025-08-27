<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\ImportLog;
use App\Entity\Framework\LsDoc;

class ImportLogTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDoc = new LsDoc();
        $messageType = 'warning';
        $message = 'Test import message';

        $importLog = new ImportLog($lsDoc, $messageType, $message);

        $this->assertInstanceOf(ImportLog::class, $importLog);
        $this->assertEquals($lsDoc, $importLog->lsDoc);
        $this->assertEquals($messageType, $importLog->messageType);
        $this->assertEquals($message, $importLog->message);
        $this->assertFalse($importLog->read);
    }

    public function testConstructorWithNullLsDoc()
    {
        $messageType = 'error';
        $message = 'Test error message';

        $importLog = new ImportLog(null, $messageType, $message);

        $this->assertNull($importLog->lsDoc);
        $this->assertEquals($messageType, $importLog->messageType);
        $this->assertEquals($message, $importLog->message);
    }

    public function testConstructorWithNullMessage()
    {
        $lsDoc = new LsDoc();
        $messageType = 'info';

        $importLog = new ImportLog($lsDoc, $messageType, null);

        $this->assertNull($importLog->message);
        $this->assertEquals($messageType, $importLog->messageType);
    }


    public function testDefaultMessageType()
    {
        $lsDoc = new LsDoc();
        $importLog = new ImportLog($lsDoc, message: 'test message');

        $this->assertEquals('warning', $importLog->messageType);
    }

    public function testDefaultReadStatus()
    {
        $lsDoc = new LsDoc();
        $importLog = new ImportLog($lsDoc, 'error', 'test message');

        $this->assertFalse($importLog->read);
    }

    public function testDifferentMessageTypes()
    {
        $lsDoc = new LsDoc();
        $messageTypes = ['error', 'warning', 'info', 'success', 'debug'];

        foreach ($messageTypes as $type) {
            $importLog = new ImportLog($lsDoc, $type, 'test message');
            $this->assertEquals($type, $importLog->messageType);
        }
    }

    public function testLongMessage()
    {
        $lsDoc = new LsDoc();
        $longMessage = str_repeat('This is a very long import message that should be stored properly. ', 10);

        $importLog = new ImportLog($lsDoc, 'warning', $longMessage);

        $this->assertEquals($longMessage, $importLog->message);
    }

    public function testEmptyMessage()
    {
        $lsDoc = new LsDoc();
        $importLog = new ImportLog($lsDoc, 'info', '');

        $this->assertEquals('', $importLog->message);
    }

    public function testSpecialCharactersInMessage()
    {
        $lsDoc = new LsDoc();
        $specialMessage = 'Message with special chars: @#$%^&*()_+{}|:<>?[]\;\'",./';

        $importLog = new ImportLog($lsDoc, 'warning', $specialMessage);

        $this->assertEquals($specialMessage, $importLog->message);
    }

    public function testUnicodeCharactersInMessage()
    {
        $lsDoc = new LsDoc();
        $unicodeMessage = 'Unicode message: Üñíçødé 中文 العربية';

        $importLog = new ImportLog($lsDoc, 'info', $unicodeMessage);

        $this->assertEquals($unicodeMessage, $importLog->message);
    }

    public function testLsDocRelationship()
    {
        $lsDoc = new LsDoc();
        $lsDoc->setTitle('Test Framework');
        $importLog = new ImportLog($lsDoc, 'warning', 'test message');

        $this->assertEquals($lsDoc, $importLog->lsDoc);
        $this->assertEquals('Test Framework', $importLog->lsDoc->getTitle());
    }

    public function testReadPropertyIsPublic()
    {
        $lsDoc = new LsDoc();
        $importLog = new ImportLog($lsDoc, 'error', 'test');

        // Test that read property can be accessed and modified
        $this->assertFalse($importLog->read);
        $importLog->read = true;
        $this->assertTrue($importLog->read);
    }

    public function testReadonlyProperties()
    {
        $lsDoc = new LsDoc();
        $originalLsDoc = $lsDoc;
        $originalMessageType = 'warning';
        $originalMessage = 'test message';

        $importLog = new ImportLog($originalLsDoc, $originalMessageType, $originalMessage);

        // Test that readonly properties maintain their values
        $this->assertEquals($originalLsDoc, $importLog->lsDoc);
        $this->assertEquals($originalMessageType, $importLog->messageType);
        $this->assertEquals($originalMessage, $importLog->message);
    }

    public function testConstructorWithAllParameters()
    {
        $lsDoc = new LsDoc();
        $messageType = 'error';
        $message = 'Detailed error message with context';

        $importLog = new ImportLog($lsDoc, $messageType, $message);

        $this->assertEquals($lsDoc, $importLog->lsDoc);
        $this->assertEquals($messageType, $importLog->messageType);
        $this->assertEquals($message, $importLog->message);
        $this->assertFalse($importLog->read);
    }

    public function testMultipleImportLogs()
    {
        $lsDoc = new LsDoc();

        $log1 = new ImportLog($lsDoc, 'error', 'First error');
        $log2 = new ImportLog($lsDoc, 'warning', 'Second warning');
        $log3 = new ImportLog($lsDoc, 'info', 'Third info');

        $this->assertEquals($lsDoc, $log1->lsDoc);
        $this->assertEquals($lsDoc, $log2->lsDoc);
        $this->assertEquals($lsDoc, $log3->lsDoc);

        $this->assertEquals('error', $log1->messageType);
        $this->assertEquals('warning', $log2->messageType);
        $this->assertEquals('info', $log3->messageType);

        $this->assertEquals('First error', $log1->message);
        $this->assertEquals('Second warning', $log2->message);
        $this->assertEquals('Third info', $log3->message);
    }

    public function testImportLogWithoutLsDoc()
    {
        $importLog = new ImportLog(null, 'warning', 'Message without document');

        $this->assertNull($importLog->lsDoc);
        $this->assertEquals('warning', $importLog->messageType);
        $this->assertEquals('Message without document', $importLog->message);
    }

    public function testMessageTypeValidation()
    {
        $lsDoc = new LsDoc();

        // Test various message types that might be used
        $validTypes = ['error', 'warning', 'info', 'success', 'debug', 'notice'];

        foreach ($validTypes as $type) {
            $importLog = new ImportLog($lsDoc, $type, 'test message');
            $this->assertEquals($type, $importLog->messageType);
        }
    }
}
