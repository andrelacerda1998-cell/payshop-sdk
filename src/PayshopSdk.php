<?php

namespace RwInteractive\PayshopSdk;

use RwInteractive\PayshopSdk\Api\Customer;
use RwInteractive\PayshopSdk\Api\PaymentMethods\PaymentsMethods;
use RwInteractive\PayshopSdk\Api\Payments\PaymentOrders;

class PayshopSdk {

    /**
     * The default customer model class name.
     *
     * @var string
     */
    public static $customerModel = 'App\\Models\\User';


    /**
     * Set the customer model class name.
     *
     * @param  string  $customerModel
     * @return void
     */
    public static function useCustomerModel($customerModel)
    {
        static::$customerModel = $customerModel;
    }

    public static function getApiBaseUrl()
    {
        if (config('payshop-sdk.environment') === 'sandbox') {
            return config('payshop-sdk.api_endpoint.sandbox');
        }else{
            return config('payshop-sdk.api_endpoint.production');
        }
    }

    public static function customers()
    {
        return Customer::make();
    }

    public static function paymentMethods(): PaymentsMethods
    {
        return new PaymentsMethods();
    }

    public static function paymentOrders(): PaymentOrders
    {
        return new PaymentOrders();
    }
}
