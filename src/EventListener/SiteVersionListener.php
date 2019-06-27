<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SiteVersionListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    public $projectDir;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(string $projectDir, CacheInterface $cache)
    {
        $this->projectDir = $projectDir;
        $this->cache = $cache;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onKernelResponse', 200]];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $fullVersion = $this->getFullVersion();

        $response = $event->getResponse();
        $response->headers->set('X-OpenSALT', $fullVersion);
    }

    private function getFullVersion(): string
    {
        $fullVersion = $this->cache->get('version', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->getUncachedVersion();
        });

        return $fullVersion;
    }

    private function getUncachedVersion(): string
    {
        if (file_exists($this->projectDir.'/public/version.txt')) {
            return trim(file_get_contents($this->projectDir.'/public/version.txt'));
        }

        if (file_exists($this->projectDir.'/VERSION')) {
            return trim(file_get_contents($this->projectDir.'/VERSION'));
        }

        return 'UNKNOWN';
    }
}
