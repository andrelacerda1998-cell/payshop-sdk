<?php

namespace RwInteractive\PayshopSdk\Api\PaymentMethods;

use Illuminate\Support\Str;
use RwInteractive\PayshopSdk\Api\Concerns\APIRequest;
use RwInteractive\PayshopSdk\Concerns\PaymentMethods\HasServices;
use RwInteractive\PayshopSdk\Enums\Services\PaymentProvider;
use RwInteractive\PayshopSdk\Exceptions\Api\ValidationFailed;
use RwInteractive\PayshopSdk\PayshopSdk;
use SensitiveParameter;

class CreditCard
{
    use APIRequest, HasServices;

    private PaymentProvider $paymentProvider = PaymentProvider::CreditCard;

    public function create(#[SensitiveParameter] $creditCardNumber, #[SensitiveParameter] $cvv, #[SensitiveParameter] $expirationMonth, #[SensitiveParameter] $expirationYear, #[SensitiveParameter] $holderName, $customerId)
    {
        $response = $this->post($this->getEndpoint(), [
            "card_holder" => $holderName,
            "card_pan" => $creditCardNumber,
            "card_expiry_month" => $expirationMonth,
            "card_expiry_year" => $expirationYear,
            "customer_ext_id" => $customerId,
            "validate" => true,
            "card_cvv" => $cvv,
            "service" => $this->getServiceUUID()
        ], false);

        if (!$this->isSuccess($response)){
            throw new ValidationFailed($response['response']['details']);
        }

        return $response['response']['Source'];
    }

    public function getEndpoint(): string
    {
        return 'payment-method/card';
    }
}
