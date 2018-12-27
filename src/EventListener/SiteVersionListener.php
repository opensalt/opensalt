<?php

namespace App\EventListener;

use Symfony\Component\Cache\Simple\ApcuCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SiteVersionListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    public $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::RESPONSE => ['onKernelResponse', 200]];
    }

    public function onKernelResponse(FilterResponseEvent $event): void
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
        $cache = new ApcuCache('opensalt');
        if (!$fullVersion = $cache->get('version')) {
            $fullVersion = $this->getUncachedVersion();
            $cache->set('version', $fullVersion, 3600);
        }

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
