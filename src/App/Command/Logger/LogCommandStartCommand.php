<?php

namespace App\Command\Logger;

use App\Command\CommandInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class LogCommandStartCommand extends GenericEvent implements CommandInterface
{

}
