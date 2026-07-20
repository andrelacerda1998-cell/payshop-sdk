<?php

namespace RwInteractive\PayshopSdk\Exceptions\Api;

use Throwable;

class CreditCardValidationRequired extends \Exception
{
    public function __construct(private string $url, string $message = "Credit Card validation required", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
