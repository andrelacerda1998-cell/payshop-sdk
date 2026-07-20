<?php

namespace RwInteractive\PayshopSdk\Exceptions\Api;

use Throwable;

class InvalidAuthentication extends \Exception
{
    public function __construct(string $message = 'The API token provided is invalid.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
