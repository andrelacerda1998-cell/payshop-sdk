<?php

namespace RwInteractive\PayshopSdk\Exceptions\Api;

use Throwable;

class OperationInvalid extends \Exception
{
    public function __construct(string $message = "Operation not allowed for this payment order.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
