<?php

namespace App\Handler\Framework;

use App\Handler\BaseValidatedHandler;
use App\Service\FrameworkService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class BaseFrameworkHandler
 */
abstract class BaseFrameworkHandler extends BaseValidatedHandler
{
    /**
     * @var FrameworkService
     */
    protected $framework;

    /**
     * BaseFrameworkHandler constructor.
     *
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "framework" = @DI\Inject(App\Service\FrameworkService::class)
     * })
     *
     * @param ValidatorInterface $validator
     * @param FrameworkService $framework
     */
    public function __construct(ValidatorInterface $validator, FrameworkService $framework)
    {
        parent::__construct($validator);
        $this->framework = $framework;
    }
}
