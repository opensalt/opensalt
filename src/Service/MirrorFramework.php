<?php

namespace App\Service;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportCaseJsonCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\Mirror\Framework;
use App\Entity\Framework\Mirror\Log;
use App\Entity\Framework\Mirror\OAuthCredential;
use App\Exception\MirrorAlreadyChangedException;
use App\Exception\MirrorIdConflictException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Swaggest\JsonSchema\Schema;

class MirrorFramework
{
    use CommandDispatcherTrait;
    use LoggerTrait;

    /**
     * @var MirrorServer
     */
    private $mirrorServer;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(MirrorServer $mirrorServer, ManagerRegistry $managerRegistry)
    {
        $this->mirrorServer = $mirrorServer;
        $this->em = $managerRegistry->getManager();
        $this->managerRegistry = $managerRegistry;
    }

    public function validate(string $json): void
    {
        try {
            $schema = Schema::import(json5_decode(file_get_contents(__DIR__.'/../../config/schema/case-v1p0-cfpackage-schema.json')));
            $schema->in(json5_decode($json));
            $schema = null;
        } catch (\Exception $e) {
            throw new \RuntimeException('CFPackage not valid', 0, $e);
        }
    }

    public function fetchFramework(string $url, ?OAuthCredential $credentials = null): string
    {
        return $this->mirrorServer->fetchUrlWithCredentials($url, $credentials);
    }

    public function mirrorNext(): ?Framework
    {
        // Get next framework to mirror, based on schedule and priority
        /** @var Framework $next */
        $next = $this->em->getRepository(Framework::class)->findNext();
        if (null === $next) {
            return null;
        }

        try {
            if (!$this->em->getRepository(Framework::class)->markAsProcessing($next)) {
                throw new MirrorAlreadyChangedException('Could not mark framework as being processed, possibly already being processed.');
            }
            $this->em->refresh($next);
            $mirrored = $this->mirror($next);
            $next->markSuccess($mirrored);
            $next->setLastSuccessContent($next->getLastContent());

            if ($mirrored) {
                $log = $next->addLog(Log::STATUS_SUCCESS, 'Updated framework from mirrored server');
                $this->em->persist($log);
            }

            $this->em->flush();
        } catch (MirrorAlreadyChangedException $e) {
            throw $e;
        } catch (\Exception $e) {
            if (!$this->em->isOpen()) {
                // A SQL Exception causes the EntityManager to close
                // Reset it so we can store the error
                $this->em->clear();
                $this->managerRegistry->resetManager();
                $this->em = $this->managerRegistry->getManager();

                $next = $this->em->getRepository(Framework::class)->find($next->getId());
            }

            $msg = $e->getMessage();
            $errorType = Framework::ERROR_GENERAL;
            if ($e instanceof UniqueConstraintViolationException) {
                $msg = $e->getPrevious()->getMessage();
            }
            if ($e instanceof MirrorIdConflictException) {
                $errorType = Framework::ERROR_ID_CONFLICT;
            }

            $next->markFailure($errorType);
            $log = $next->addLog(Log::STATUS_FAILURE, $msg);
            $this->em->persist($log);
            $this->em->flush();

            $this->warning('Error mirroring framework.', [
                'identifier' => $next->getIdentifier(),
                'error' => $msg,
            ]);

            throw new \RuntimeException('Error mirroring framework: '.$msg);
        }

        return $next;
    }

    private function mirror(Framework $next): bool
    {
        ini_set('memory_limit', '2G');
        set_time_limit(600); // increase time limit for large files

        $this->checkForLocalFramework($next);

        $framework = $this->fetchFramework($next->getUrl(), $next->getServer()->getCredentials());

        if ($next->matchesLastSuccessContent($framework) && (null === $next->getLastFailure() || $next->getLastFailure() < $next->getLastChange())) {
            // No change
            return false;
        }

        $next->setLastContent($framework);
        $this->em->flush($next);

        $command = new ImportCaseJsonCommand($framework);
        $this->sendCommand($command);

        if (null === $next->getFramework()) {
            /** @var LsDoc $doc */
            $doc = $this->em->getRepository(LsDoc::class)->findOneByIdentifier($next->getIdentifier());
            $doc->setMirroredFramework($next);
            $next->setFramework($doc);
            $this->em->flush($doc);
        }

        return true;
    }

    private function checkForLocalFramework(Framework $next): void
    {
        if (null !== $next->getFramework()) {
            return;
        }

        /** @var LsDoc $doc */
        $localDoc = $this->em->getRepository(LsDoc::class)->findOneByIdentifier($next->getIdentifier());
        if (null === $localDoc) {
            return;
        }

        throw new MirrorIdConflictException('A framework already exists on the server with the same identifier');
    }
}
