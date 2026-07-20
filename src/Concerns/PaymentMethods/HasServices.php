<?php

namespace RwInteractive\PayshopSdk\Concerns\PaymentMethods;

trait HasServices
{
    public function getServiceUUID()
    {
        return config('payshop-sdk.paymentServices.'.$this->paymentProvider->value);
    }
}
