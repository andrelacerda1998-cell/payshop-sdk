<?php

namespace RwInteractive\PayshopSdk\Concerns\Customer;

use Carbon\Carbon;
use RwInteractive\PayshopSdk\Enums\Payment\OperationType;
use RwInteractive\PayshopSdk\Enums\PaymentMethods\PaymentMethodType;
use RwInteractive\PayshopSdk\Enums\Services\PaymentProvider;
use RwInteractive\PayshopSdk\Exceptions\Api\CreditCardValidationRequired;
use RwInteractive\PayshopSdk\Exceptions\InvalidPaymentMethod;
use RwInteractive\PayshopSdk\Models\PaymentMethod;
use RwInteractive\PayshopSdk\Models\PaymentOrder;
use RwInteractive\PayshopSdk\PayshopSdk;

trait Payments
{
    /**
     * @throws CreditCardValidationRequired
     */
    public function createPaymentOrder(
        OperationType $operationType,
        int $amount,
        string $description,
        Carbon $expiresIn,
        array $params = [],
        array $routeParams = [],
        PaymentProvider $provider = PaymentProvider::CreditCard,
    ):PaymentOrder
    {
        PayshopSdk::customers()->updateProfile(
            $this->getFirstName(),
            $this->getLastName(),
            $this->getEmail(),
            $this->getCustomerKey(),
            $this->getPhoneNumber(),
        );

        $response = PayshopSdk::paymentOrders()->orders->createWithCreditCard(
            $operationType,
            $amount,
            $description,
            $expiresIn,
            $this->getCustomerKey(),
            $routeParams
        );

        $data = $response['order'];
        $data['ip']=$data['ip']??'';
        $data['user_id'] = $this->id;
        $data['type'] = $operationType;

        $paymentOrder = PaymentOrder::create($data);
        $paymentOrder->save();
        return $paymentOrder;
    }

    /**
     * @throws CreditCardValidationRequired
     */
    public function createMbWayPaymentOrder(
        OperationType $operationType,
        int $amount,
        string $description,
        Carbon $expiresIn,
        PaymentMethod $paymentMethod,
        array $routeParams = [],
    ): PaymentOrder
    {
        if ($paymentMethod->type !== PaymentMethodType::MBWAY) {
            throw new InvalidPaymentMethod();
        }

        $response = PayshopSdk::paymentOrders()->orders->createWithMbWay(
            $operationType,
            $amount,
            $description,
            $expiresIn,
            $this->getCustomerKey(),
            $routeParams,
            $paymentMethod->phone_number,
            $this->getFirstName(),
            $this->getLastName()
        );

        $data = $response['order'];
        $data['ip']=$data['ip']??'';
        $data['user_id'] = $this->id;
        $data['type'] = $operationType;

        $paymentOrder = PaymentOrder::create($data);
        $paymentOrder->save();
        return $paymentOrder;
    }

    public function paymentOrders()
    {
        return $this->hasMany(PaymentOrder::class);
    }
}
