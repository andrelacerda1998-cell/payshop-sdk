<?php
use \RwInteractive\PayshopSdk\Enums\Services\PaymentProvider;

return [
    'environment' => env('PAYSHOP_SDK_ENVIRONMENT', 'sandbox'),
    'client' => [
        'uuid' => env('PAYSHOP_SDK_CLIENT_UUID'),
    ],
    'api' =>[
        'key' => env('PAYSHOP_SDK_API_KEY'),
        'signature' => env('PAYSHOP_SDK_API_SIGNATURE'),
    ],
    'UserModel' => \App\Models\User::class,
    'user_table' => 'users',
    'api_endpoint' => [
        'sandbox' => 'https://api.paylands.com/v1/sandbox/',
        'production' => 'https://api.paylands.com/v1/',
    ],
    'currency' => 'EUR',
    // Timeouts HTTP (segundos). Sem isto o Guzzle espera indefinidamente numa
    // partição de rede, prendendo chamadas de pagamento. Reads/polls do Payshop
    // respondem em <1s, POSTs de criação são instantâneos — 15s/5s é folgado.
    'timeout' => (float) env('PAYSHOP_SDK_TIMEOUT', 15),
    'connect_timeout' => (float) env('PAYSHOP_SDK_CONNECT_TIMEOUT', 5),
    // Fail-closed da verificação de assinatura de resposta. Quando true, respostas sem
    // validation_hash são rejeitadas em vez de aceites. Default false para compatibilidade —
    // ativar só depois de confirmar em sandbox que o Payshop assina todas as respostas 2xx.
    'strict_signature' => (bool) env('PAYSHOP_SDK_STRICT_SIGNATURE', false),
    'paymentServices' => [
        'creditCard' => env('PAYSHOP_SDK_CREDIT_CARD_SERVICE_UUID'),
        'mbWay' => env('PAYSHOP_SDK_MBWAY_SERVICE_UUID'),
    ],
    'url' => [
        'success' => 'payshop_success_order',
        'failure' => 'payshop_failure_order',
    ],
    'customer_prefix' => env('PAYSHOP_SDK_CUSTOMER_PREFIX', config('app.env')),
];
