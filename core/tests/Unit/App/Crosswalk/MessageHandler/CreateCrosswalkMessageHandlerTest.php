<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\MessageHandler;

use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Message\ProcessCrosswalkBatchMessage;
use App\Crosswalk\MessageHandler\CreateCrosswalkMessageHandler;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsItem;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Ramsey\Uuid\Uuid;

final class CreateCrosswalkMessageHandlerTest extends TestCase
{
    private function createJob(): CrosswalkJob
    {
        $job = new CrosswalkJob(42, 87, 156);
        $jobRef = new \ReflectionProperty($job, 'id');
        $jobRef->setValue($job, Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'));

        return $job;
    }

    public function testMarksStartedAndDispatchesBatchMessages(): void
    {
        $message = new CreateCrosswalkMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            originFrameworkId: 42,
            destinationFrameworkId: 87,
            crosswalkFrameworkId: 156,
            threshold: 0.75,
            exactMatchThreshold: 0.90,
        );

        $job = $this->createJob();

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $sourceItem1 = $this->createMock(LsItem::class);
        $sourceItem1->method('getId')->willReturn(100);
        $sourceItem2 = $this->createMock(LsItem::class);
        $sourceItem2->method('getId')->willReturn(101);

        $crosswalkService = $this->createMock(CrosswalkService::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function () use ($sourceItem1, $sourceItem2) {
            $repo = $this->createMock(EntityRepository::class);
            $repo->method('findBy')->willReturn([$sourceItem1, $sourceItem2]);

            return $repo;
        });

        $hub = $this->createMock(HubInterface::class);

        $dispatchedMessages = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willReturnCallback(function (object $msg) use (&$dispatchedMessages) {
                $dispatchedMessages[] = $msg;

                return new Envelope($msg);
            });

        $handler = new CreateCrosswalkMessageHandler(
            $jobRepo, $crosswalkService, $em, $hub, $messageBus,
        );

        $handler->__invoke($message);

        $this->assertSame('running', $job->status);
        $this->assertSame(2, $job->totalItems);

        $this->assertCount(1, $dispatchedMessages);
        $this->assertInstanceOf(ProcessCrosswalkBatchMessage::class, $dispatchedMessages[0]);
        $this->assertSame([100, 101], $dispatchedMessages[0]->itemIds);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $dispatchedMessages[0]->jobId);
    }

    public function testMarksCompletedImmediatelyWhenNoItems(): void
    {
        $message = new CreateCrosswalkMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            originFrameworkId: 42,
            destinationFrameworkId: 87,
            crosswalkFrameworkId: 156,
        );

        $job = $this->createJob();

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn($job);

        $crosswalkService = $this->createMock(CrosswalkService::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function () {
            $repo = $this->createMock(EntityRepository::class);
            $repo->method('findBy')->willReturn([]);

            return $repo;
        });

        $hub = $this->createMock(HubInterface::class);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');

        $handler = new CreateCrosswalkMessageHandler(
            $jobRepo, $crosswalkService, $em, $hub, $messageBus,
        );

        $handler->__invoke($message);

        $this->assertSame('completed', $job->status);
        $this->assertSame(0, $job->totalItems);
    }

    public function testReturnsEarlyIfJobNotFound(): void
    {
        $message = new CreateCrosswalkMessage(
            jobId: 'nonexistent',
            originFrameworkId: 42,
            destinationFrameworkId: 87,
            crosswalkFrameworkId: 156,
        );

        $jobRepo = $this->createMock(CrosswalkJobRepository::class);
        $jobRepo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $hub = $this->createMock(HubInterface::class);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');

        $handler = new CreateCrosswalkMessageHandler(
            $jobRepo,
            $this->createMock(CrosswalkService::class),
            $em,
            $hub,
            $messageBus,
        );

        $handler->__invoke($message);

        $this->assertTrue(true);
    }
}
