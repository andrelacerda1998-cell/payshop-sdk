<?php

namespace RwInteractive\PayshopSdk\Exceptions\Api;

use Throwable;

class ApiKeyNotFoundException extends \Exception
{
    public function __construct(string $message = "Api Key Not Found", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
