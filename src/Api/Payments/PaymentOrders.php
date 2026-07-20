<?php

namespace RwInteractive\PayshopSdk\Api\Payments;

final class PaymentOrders
{
    public PaymentOrder $orders;

    public function __construct()
    {
        $this->orders = PaymentOrder::make();
    }
}
