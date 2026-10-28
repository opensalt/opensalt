<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Command\Framework;

use App\Command\Framework\CloneFrameworkCommand;
use App\Entity\Framework\LsDoc;
use PHPUnit\Framework\TestCase;

class CloneFrameworkCommandTest extends TestCase
{
    public function testDefaultsDoNotCopyAssociationsOrLinkOriginals(): void
    {
        $doc = $this->createMock(LsDoc::class);
        $command = new CloneFrameworkCommand($doc);

        $this->assertSame($doc, $command->getDoc());
        $this->assertFalse($command->associateWithOriginal());
        $this->assertFalse($command->copyAssociations());
    }

    public function testCloneOptionsAreStored(): void
    {
        $doc = $this->createMock(LsDoc::class);
        $command = new CloneFrameworkCommand($doc, true, true);

        $this->assertTrue($command->associateWithOriginal());
        $this->assertTrue($command->copyAssociations());
    }
}
