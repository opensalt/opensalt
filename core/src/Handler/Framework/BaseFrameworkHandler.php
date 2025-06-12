<?php

declare(strict_types=1);

namespace App\Handler\Framework;

use App\Handler\BaseValidatedHandler;
use App\Service\FrameworkService;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseFrameworkHandler extends BaseValidatedHandler
{
    public function __construct(ValidatorInterface $validator, protected FrameworkService $framework)
    {
        parent::__construct($validator);
    }
}
