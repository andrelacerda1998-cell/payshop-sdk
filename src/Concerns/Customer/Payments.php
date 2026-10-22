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
use RwInteractive\PayshopSdk\Support\CustomerData;

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

        // EMV 3DS (c2, c3): o telefone e o email vão também na transação, e não
        // só no perfil do cliente (pedido da Payshop a 22/10/2026). Só o que há.
        $profile = CustomerData::threeDsProfile(
            $this->getFirstName(),
            $this->getLastName(),
            $this->getEmail(),
            $this->getPhoneNumber(),
        );

        $response = PayshopSdk::paymentOrders()->orders->createWithCreditCard(
            $operationType,
            $amount,
            $description,
            $expiresIn,
            $this->getCustomerKey(),
            $routeParams,
            $profile ? [...$params, 'profile' => $profile] : $params,
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
            $this->getLastName(),
            [],
            $this->getEmail(),
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
