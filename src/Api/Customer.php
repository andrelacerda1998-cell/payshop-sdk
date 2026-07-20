<?php

namespace RwInteractive\PayshopSdk\Api;

use RwInteractive\PayshopSdk\Api\Concerns\APIRequest;

class Customer
{
    use APIRequest;

    public function create($firstName, $lastName, $email, $customerId, $phone)
    {
        $response = $this->post($this->getEndpoint(), [
            'customer_ext_id' => $customerId,
        ]);

        $this->createProfile($firstName, $lastName, $email, $customerId, $phone);

        $customerDetails = $response['response']['Customer'];

        return $customerDetails;
    }

    public function createProfile($firstName, $lastName, $email, $customerId, $phone): void
    {
        $payload = [
            'external_id' => $customerId,
            'first_name' => $firstName ?: '-',
            'last_name' => $lastName ?: $firstName ?: '-',
            'cardholder_name' => $firstName . ' ' . $lastName,
            'mobile_phone' => [
                'prefix' => '+351',
                'number' => $phone,
            ],
            'home_phone' => [
                'prefix' => '+351',
                'number' => $phone,
            ],
            'work_phone' => [
                'prefix' => '+351',
                'number' => $phone,
            ],
        ];

        if ($email !== null) {
            $payload['email'] = $email;
        }

        $this->post($this->getEndpoint().'/profile', $payload);
    }

    public function updateProfile($firstName, $lastName, $email, $customerId, $phone): void
    {
        $payload = [
            'external_id' => $customerId,
            'first_name' => $firstName ?: '-',
            'last_name' => $lastName ?: $firstName ?: '-',
            'cardholder_name' => $firstName . ' ' . $lastName,
            'mobile_phone' => [
                'prefix' => '+351',
                'number' => $phone,
            ],
            'home_phone' => [
                'prefix' => '+351',
                'number' => $phone,
            ],
            'work_phone' => [
                'prefix' => '+351',
                'number' => $phone,
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
