<?php

namespace RwInteractive\PayshopSdk\Exceptions\Api;

use Throwable;

class ApiError extends \Exception
{
    public function __construct(string $message = "Something went wrong in API.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
