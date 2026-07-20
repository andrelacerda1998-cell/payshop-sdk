<?php

namespace RwInteractive\PayshopSdk;

use RwInteractive\PayshopSdk\Concerns\Customer\ManagePaymentsMethods;
use RwInteractive\PayshopSdk\Concerns\Customer\ManagesCustomer;
use RwInteractive\PayshopSdk\Concerns\Customer\Payments;

trait PayShopCustomer
{
    use ManagesCustomer, ManagePaymentsMethods, Payments;

    public function getCustomerKey(): string
    {
        return config('payshop-sdk.customer_prefix').'-'.$this->getKey();
    }
}
