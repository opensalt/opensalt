<?php

declare(strict_types=1);

namespace Tests\Unit\Controller\Editor;

use App\Controller\Editor\DocumentController;
use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentControllerTest extends TestCase
{
    public function testUpdateDocumentClearsAdoptionStatusWhenNull(): void
    {
        $lsDoc = new LsDoc();
        $lsDoc->setAdoptionStatus('Adopted');
        $lsDoc->setTitle('Test');

        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $controller = new DocumentController($em);
        $controller->setDispatcher($dispatcher);

        $request = new Request(
            content: json_encode(['adoptionStatus' => null], JSON_THROW_ON_ERROR)
        );

        $response = $controller->updateDocument($request, $lsDoc);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNull($lsDoc->getAdoptionStatus());
    }

    public function testUpdateDocumentDoesNotClearAdoptionStatusWhenAbsent(): void
    {
        $lsDoc = new LsDoc();
        $lsDoc->setAdoptionStatus('Adopted');
        $lsDoc->setTitle('Test');

        $em = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $controller = new DocumentController($em);
        $controller->setDispatcher($dispatcher);

        // adoptionStatus key entirely absent from payload
        $request = new Request(
            content: json_encode(['title' => 'Updated'], JSON_THROW_ON_ERROR)
        );

        $response = $controller->updateDocument($request, $lsDoc);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Adopted', $lsDoc->getAdoptionStatus());
        $this->assertSame('Updated', $lsDoc->getTitle());
    }

    public function testUpdateDocumentClearsNoteWhenNull(): void
    {
        $lsDoc = new LsDoc();
        $lsDoc->setTitle('Test');
        $lsDoc->setNote('Existing note');

        $controller = $this->createDocumentController();

        $request = new Request(
            content: json_encode(['note' => null], JSON_THROW_ON_ERROR)
        );

        $controller->updateDocument($request, $lsDoc);

        $this->assertNull($lsDoc->getNote());
    }

    public function testUpdateDocumentClearsNoteWhenEmptyString(): void
    {
        $lsDoc = new LsDoc();
        $lsDoc->setTitle('Test');
        $lsDoc->setNote('Existing note');

        $controller = $this->createDocumentController();

        $request = new Request(
            content: json_encode(['note' => ''], JSON_THROW_ON_ERROR)
        );

        $controller->updateDocument($request, $lsDoc);

        $this->assertNull($lsDoc->getNote());
    }

    public function testUpdateDocumentClearsStatusStartWhenNull(): void
    {
        $lsDoc = new LsDoc();
        $lsDoc->setTitle('Test');
        $lsDoc->setStatusStart(new \DateTime('2020-01-01'));

        $controller = $this->createDocumentController();

        $request = new Request(
            content: json_encode(['statusStart' => null], JSON_THROW_ON_ERROR)
        );

        $controller->updateDocument($request, $lsDoc);

        $this->assertNull($lsDoc->getStatusStart());
    }

    public function testUpdateDocumentDoesNotClearNoteWhenAbsent(): void
    {
        $lsDoc = new LsDoc();
        $lsDoc->setTitle('Test');
        $lsDoc->setNote('Keep me');

        $controller = $this->createDocumentController();

        $request = new Request(
            content: json_encode(['title' => 'Updated'], JSON_THROW_ON_ERROR)
        );

        $controller->updateDocument($request, $lsDoc);

        $this->assertSame('Keep me', $lsDoc->getNote());
    }

    private function createDocumentController(): DocumentController
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $controller = new DocumentController($em);
        $controller->setDispatcher($this->createMock(EventDispatcherInterface::class));

        return $controller;
    }
}
