<?php

namespace RwInteractive\PayshopSdk\Api\PaymentMethods;

final class PaymentsMethods
{
    public CreditCard $creditCard;

    public function __construct()
    {
        $this->creditCard = CreditCard::make();
    }
}
