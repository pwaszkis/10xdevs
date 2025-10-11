<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when user exceeds monthly AI generation limit.
 */
class LimitExceededException extends Exception {}
