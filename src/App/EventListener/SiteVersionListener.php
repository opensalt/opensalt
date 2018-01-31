<?php

namespace App\EventListener;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Cache\Simple\ApcuCache;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @DI\Service()
 */
class SiteVersionListener
{
    /**
     * @var string
     */
    public $rootDir;

    /**
     * @DI\InjectParams({
     *     "rootDir" = @DI\Inject("%kernel.root_dir%")
     * })
     */
    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * @param FilterResponseEvent $event
     *
     * @DI\Observe(KernelEvents::RESPONSE, priority=200)
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $cache = new ApcuCache('opensalt');
        if (!$fullVersion = $cache->get('version')) {
            $rootDir = $this->rootDir;
            $webDir = \dirname($rootDir).'/web';

            if (file_exists($webDir.'/version.txt')) {
                $fullVersion = trim(file_get_contents($webDir.'/version.txt'));
            } elseif (file_exists($rootDir.'/../VERSION')) {
                $fullVersion = trim(file_get_contents($rootDir.'/../VERSION'));
            } else {
                $fullVersion = 'UNKNOWN';
            }

            $cache->set('version', $fullVersion, 3600);
        }

        $response = $event->getResponse();
        $response->headers->set('X-OpenSALT', $fullVersion);
    }
}
