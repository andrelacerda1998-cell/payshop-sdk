<?php

namespace RwInteractive\PayshopSdk\Api;

use RwInteractive\PayshopSdk\Api\Concerns\APIRequest;
use RwInteractive\PayshopSdk\Exceptions\Api\ApiError;
use RwInteractive\PayshopSdk\Support\CustomerData;

class Customer
{
    use APIRequest;

    public function create($firstName, $lastName, $email, $customerId, $phone)
    {
        $response = $this->post($this->getEndpoint(), [
            'customer_ext_id' => $customerId,
        ]);

        try {
            $this->createProfile($firstName, $lastName, $email, $customerId, $phone);
        } catch (ApiError $e) {
            // 409 "Customer profile already exists": o perfil ficou criado numa
            // tentativa anterior que não chegou a guardar o token do lado da
            // app. Sem isto, o cliente nunca mais conseguia juntar um cartão
            // (cada tentativa batia no mesmo 409). O cliente existe e o token
            // veio no pedido acima: atualiza-se o perfil e segue.
            if ($e->getCode() !== 409) {
                throw $e;
            }

            $this->updateProfile($firstName, $lastName, $email, $customerId, $phone);
        }

        $customerDetails = $response['response']['Customer'];

        return $customerDetails;
    }

    public function createProfile($firstName, $lastName, $email, $customerId, $phone): void
    {
        // O servidor guarda "+351912345678": com o prefixo à parte, o número
        // ia com o 351 duas vezes. Um número estrangeiro vai como veio.
        $numero = CustomerData::portuguesePhone($phone) ?? $phone;

        $payload = [
            'external_id' => $customerId,
            'first_name' => $firstName ?: '-',
            'last_name' => $lastName ?: $firstName ?: '-',
            'cardholder_name' => $firstName . ' ' . $lastName,
            'mobile_phone' => [
                'prefix' => '+351',
                'number' => $numero,
            ],
            'home_phone' => [
                'prefix' => '+351',
                'number' => $numero,
            ],
            'work_phone' => [
                'prefix' => '+351',
                'number' => $numero,
            ],
        ];

        if ($email !== null) {
            $payload['email'] = $email;
        }

        $this->post($this->getEndpoint().'/profile', $payload);
    }

    public function updateProfile($firstName, $lastName, $email, $customerId, $phone): void
    {
        // O servidor guarda "+351912345678": com o prefixo à parte, o número
        // ia com o 351 duas vezes. Um número estrangeiro vai como veio.
        $numero = CustomerData::portuguesePhone($phone) ?? $phone;

        $payload = [
            'external_id' => $customerId,
            'first_name' => $firstName ?: '-',
            'last_name' => $lastName ?: $firstName ?: '-',
            'cardholder_name' => $firstName . ' ' . $lastName,
            'mobile_phone' => [
                'prefix' => '+351',
                'number' => $numero,
            ],
            'home_phone' => [
                'prefix' => '+351',
                'number' => $numero,
            ],
            'work_phone' => [
                'prefix' => '+351',
                'number' => $numero,
            ],
        ];

        if ($email !== null) {
            $payload['email'] = $email;
        }

        $this->put($this->getEndpoint().'/profile', $payload);
    }

    public function getEndpoint(): string
    {
        return 'customer';
    }
}
