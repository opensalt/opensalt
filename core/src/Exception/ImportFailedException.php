<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Thrown when a framework import fails for a reason that is meaningful to the
 * user (bad file content, duplicate identifiers, ...). The message is safe to
 * show in the import UI.
 */
final class ImportFailedException extends \RuntimeException
{
}
