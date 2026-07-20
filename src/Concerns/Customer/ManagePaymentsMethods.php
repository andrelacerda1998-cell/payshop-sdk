<?php

namespace RwInteractive\PayshopSdk\Concerns\Customer;

use RwInteractive\PayshopSdk\Enums\PaymentMethods\PaymentMethodType;
use RwInteractive\PayshopSdk\Exceptions\Api\ValidationFailed;
use RwInteractive\PayshopSdk\Models\PaymentMethod;
use RwInteractive\PayshopSdk\PayshopSdk;
use SensitiveParameter;

trait ManagePaymentsMethods
{
    public function addCreditCard(#[SensitiveParameter] $creditCardNumber, #[SensitiveParameter] $cvv, #[SensitiveParameter] $expirationMonth, #[SensitiveParameter] $expirationYear, #[SensitiveParameter] $holderName)
    {
        $creditCardNumber = str_replace(' ', '', $creditCardNumber);
        $source = PayshopSdk::paymentMethods()->creditCard->create($creditCardNumber, $cvv, $expirationMonth, $expirationYear, $holderName, $this->getCustomerKey());

        $type = PaymentMethodType::tryFrom(strtolower($source['object']));
        return $this->saveCreditCard($type, $source['uuid'], $source['token'], $source['brand'], $source['country'], $source['holder'], $source['bin'], $source['last4'], $source['expire_month'], $source['expire_year'], $source['brand_description']);

    }
    public function addMbWay(#[SensitiveParameter] $phoneNumber)
    {
        $type = PaymentMethodType::MBWAY;
        return $this->saveMbWay($type, $phoneNumber);

    }

    private function saveCreditCard(PaymentMethodType $type, string $uuid ,string $token, string $brand, string $country, string $holder, int $bin, string $last4, string $expireMonth, string $expireYear, ?string $brandDescription = '')
    {
        return $this->paymentMethods()->create([
            'type' => $type,
            'uuid' => $uuid,
            'token' => $token,
            'brand' => $brand,
            'country' => $country,
            'holder' => $holder,
            'bin' => $bin,
            'last4' => $last4,
            'expire_month' => $expireMonth,
            'expire_year' => $expireYear,
            'brand_description' => $brandDescription
        ]);
    }

    private function saveMbWay(PaymentMethodType $type, string $phoneNumber)
    {
        return $this->paymentMethods()->create([
            'type' => $type,
            'phone_number' => $phoneNumber
        ]);
    }
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }
}
