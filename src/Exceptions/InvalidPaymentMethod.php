<?php

namespace RwInteractive\PayshopSdk\Exceptions;

use Throwable;

class InvalidPaymentMethod extends \Exception
{
    public function __construct(string $message = "The payment method provided is invalid", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
