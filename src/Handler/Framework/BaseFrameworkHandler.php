<?php

namespace App\Handler\Framework;

use App\Handler\BaseValidatedHandler;
use App\Service\FrameworkService;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseFrameworkHandler extends BaseValidatedHandler
{
    /**
     * @var FrameworkService
     */
    protected $framework;

    public function __construct(ValidatorInterface $validator, FrameworkService $framework)
    {
        parent::__construct($validator);
        $this->framework = $framework;
    }
}
