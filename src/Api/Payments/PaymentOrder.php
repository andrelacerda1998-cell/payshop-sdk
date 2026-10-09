<?php

namespace RwInteractive\PayshopSdk\Api\Payments;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\URL;
use RwInteractive\PayshopSdk\Api\Concerns\APIRequest;
use RwInteractive\PayshopSdk\Enums\Payment\OperationType;
use RwInteractive\PayshopSdk\Enums\Services\PaymentProvider;
use RwInteractive\PayshopSdk\Exceptions\Api\ApiError;
use RwInteractive\PayshopSdk\Exceptions\Api\ApiMethodInvalid;
use RwInteractive\PayshopSdk\Exceptions\Api\CreditCardValidationRequired;
use RwInteractive\PayshopSdk\Exceptions\Api\InvalidAuthentication;
use RwInteractive\PayshopSdk\Models\PaymentMethod;

class PaymentOrder
{
    use APIRequest;

    /**
     * @throws CreditCardValidationRequired
     */
    public function createWithCreditCard(
        OperationType $operationType,
        int           $amount,
        string        $description,
        Carbon        $expiresIn,
                      $customerId,
        array         $routeParams,
        array         $params = [],
    ): array
    {
        return $this->create($operationType, $amount, $description, $expiresIn, $customerId, $params, $routeParams, PaymentProvider::CreditCard);
    }

    /**
     * @throws CreditCardValidationRequired
     */
    public function createWithMbWay(
        OperationType $operationType,
        int           $amount,
        string        $description,
        Carbon        $expiresIn,
                      $customerId,
        array         $routeParams,
                      $mbwayNumber,
                      $firstName,
                      $lastName,
        array         $params = [],
    )
    {
        $params = [
            ...$params,
            'profile' => [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "phone" => [
                    "number" => $mbwayNumber,
                    "prefix" => "351"
                ]
            ]
        ];

        return $this->create($operationType, $amount, $description, $expiresIn, $customerId, $params, $routeParams, PaymentProvider::MBWAY);
    }

    private function create(
        OperationType   $operationType,
        int             $amount,
        string          $description,
        Carbon          $expiresIn,
                        $customerId,
        array           $params,
        array           $routeParams,
        PaymentProvider $provider
    ): array
    {

        $postData = [
            "operative" => $operationType->name,
            "amount" => $amount,
            "description" => $description,
            "customer_ext_id" => $customerId,
            "secure" => false,
            'service' => $this->getProvider($provider),
            "expires_in" => intval(Carbon::now()->diffInSeconds($expiresIn)),
            "extra_data" => $params,
            // URLs de retorno assinadas (tamper-proof): impedem forja/enumeração das rotas
            // de callback, que apenas o browser do cliente é redirecionado a atingir.
            'url_ok' => URL::signedRoute(config('payshop-sdk.url.success'), $routeParams),
            'url_ko' => URL::signedRoute(config('payshop-sdk.url.failure'), $routeParams),
        ];

        if ($provider === PaymentProvider::MBWAY) {
            unset($postData['url_ok']);
            unset($postData['url_ko']);
        }

        // Aviso servidor-a-servidor (`url_post`): o Paylands chama este URL de
        // cada vez que a ordem muda de estado (cativada, cobrada, libertada,
        // devolvida). Ao contrário de url_ok/url_ko, não depende do browser do
        // cliente, por isso vale também para o MB Way. Só segue quando está
        // configurado -- sem a variável, a ordem é criada como sempre foi.
        $urlPost = config('payshop-sdk.notification_url');
        if (is_string($urlPost) && $urlPost !== '') {
            $postData['url_post'] = $urlPost;
        }

        $response = $this->post($this->getEndpoint(), $postData);
        if (!$this->isSuccess($response)) {
            if ($response['status'] === 303) {
                throw new CreditCardValidationRequired($response['response']['details']);
            }
            throw new Exception($response['response']['message']);
        }

        return $response['response'];
    }


    public function authorize(
        PaymentMethod                                 $paymentMethod,
        \RwInteractive\PayshopSdk\Models\PaymentOrder $paymentOrder,
        ?int                                          $amount = null,
    )
    {
        if ($amount === null) {
            $amount = $paymentOrder->amount;
        }

        $response = $this->post($this->getEndpoint() . '/direct', [
            'order_uuid' => $paymentOrder->uuid,
            'card_uuid' => $paymentMethod->uuid,
        ]);

        if (!$this->isSuccess($response)) {
            if ($response['status'] === 303) {
                throw new CreditCardValidationRequired($response['response']['details']);
            }
            throw new Exception($response['response']['message']);
        }

        $order = $response['response']['order'];

        // Lista branca: só um pré-cativo/pagamento genuinamente autorizado prossegue.
        // SUCCESS (capturado), PENDING_CONFIRMATION (cativo DEFERRED colocado). Qualquer
        // estado pendente/intermédio (PENDING_PROCESSOR_RESPONSE, PENDING_PAYMENT, os de
        // 3DS, etc.) NÃO é autorização — antes passava a lista negra e era marcado PAID sem
        // cativo. O caminho 3DS (303) já foi tratado acima com CreditCardValidationRequired.
        $okStatuses = ['SUCCESS', 'PENDING_CONFIRMATION', 'PAID'];

        if (! in_array($order['status'] ?? null, $okStatuses, true)) {
            $transaction = $order['transactions'][0] ?? null;
            $errorMessage = $transaction['error_details']['error_description']
                ?? $transaction['error']
                ?? $order['status'];

            throw new Exception($errorMessage);
        }

        return $response['response'];
    }

    public function confirmation(
        \RwInteractive\PayshopSdk\Models\PaymentOrder $paymentOrder,
        ?int                                          $amount = null,
    )
    {
        if ($amount === null) {
            $amount = $paymentOrder->amount;
        }

        $response = $this->post($this->getEndpoint() . '/confirmation', [
            'order_uuid' => $paymentOrder->uuid,
            'amount' => $amount
        ]);

        if (!$this->isSuccess($response)) {
            throw new Exception($response['response']['message']);
        }

        return $response['response'];
    }

    /**
     * @throws ApiMethodInvalid
     * @throws InvalidAuthentication
     * @throws ApiError
     * @throws Exception
     */
    public function refund(
        \RwInteractive\PayshopSdk\Models\PaymentOrder $paymentOrder,
        ?int                                          $amount = null,
    )
    {
        if ($amount === null) {
            $amount = $paymentOrder->amount;
        }

        $response = $this->post($this->getEndpoint() . '/refund', [
            'order_uuid' => $paymentOrder->uuid,
            'amount' => $amount
        ]);

        if (!$this->isSuccess($response)) {
            throw new Exception($response['response']['message']);
        }

        return $response['response'];
    }

    public function cancel(
        \RwInteractive\PayshopSdk\Models\PaymentOrder $paymentOrder
    )
    {

        $response = $this->post($this->getEndpoint() . '/cancellation', [
            'order_uuid' => $paymentOrder->uuid,
        ]);

        if (!$this->isSuccess($response)) {
            throw new Exception($response['response']['message']);
        }

        return $response['response'];
    }

    public function process(
        \RwInteractive\PayshopSdk\Models\PaymentOrder $paymentOrder
    )
    {

        $apm = match ($paymentOrder->service) {
            'SIBS' => "MBWAY",
            default => null
        };

        $response = $this->post($this->getEndpoint() . '/push', [
            'order_uuid' => $paymentOrder->uuid,
            'apm' => $apm,
        ]);

        if (!$this->isSuccess($response)) {
            throw new Exception($response['response']['message']);
        }

        return $response['response'];
    }


    public function getEndpoint(): string
    {
        return 'payment';
    }

    public function getProvider(PaymentProvider $paymentProvider)
    {
        return config('payshop-sdk.paymentServices.' . $paymentProvider->value);
    }

    public function details(\RwInteractive\PayshopSdk\Models\PaymentOrder $paymentOrder)
    {
        $reponse = $this->get('order/' . $paymentOrder->uuid);

        if (!$this->isSuccess($reponse)) {
            throw new Exception($reponse['response']);
        }

        return $reponse['response'];
    }
}
