<?php

namespace RwInteractive\PayshopSdk\Api\Payments;

use RwInteractive\PayshopSdk\Api\Concerns\APIRequest;
use RwInteractive\PayshopSdk\Enums\Payment\Wallet;
use RwInteractive\PayshopSdk\Exceptions\Api\ApiError;
use RwInteractive\PayshopSdk\Exceptions\Api\CreditCardValidationRequired;

/**
 * Pagamento de uma ordem com Apple Pay ou Google Pay.
 *
 * É o único caminho possível numa app: a documentação do PaynoPain diz que o
 * botão das carteiras não se pode mostrar num webview, por isso a app pede o
 * payload ao sistema operativo e o backend paga a ordem com ele.
 *
 * A ordem cria-se como sempre (ver PaymentOrder::create) — é ela que define o
 * `operative` e o valor. Isto só a paga. Um payload de carteira é de uso
 * único: não fica guardado como método de pagamento, ao contrário de um
 * cartão tokenizado.
 */
class WalletPayment
{
    use APIRequest;

    /**
     * @param  array|string  $payload  o que a carteira devolveu, tal e qual.
     *                                 Apple Pay: o `paymentData` do PKPaymentToken.
     *                                 Google Pay: o objeto PaymentData completo.
     * @return array a ordem, no mesmo formato que o resto do SDK devolve
     *
     * @throws CreditCardValidationRequired quando falta 3DS (só Google Pay);
     *                                      `getUrl()` traz o URL do desafio
     * @throws ApiError
     */
    public function pay(string $orderUuid, Wallet $wallet, array|string $payload, ?string $customerIp = null): array
    {
        $data = [
            'order_uuid' => $orderUuid,
            'wallet' => $wallet->value,
            'payload' => $payload,
        ];

        if ($customerIp !== null && $customerIp !== '') {
            $data['customer_ip'] = $customerIp;
        }

        // `throwable: false` para o 303 chegar aqui como resposta em vez de
        // exceção genérica: um 303 não é uma falha, é o banco a pedir 3DS.
        $response = $this->post($this->getEndpoint(), $data, false);

        if ($response['status'] === 303) {
            // Mesma forma que o pagamento com cartão: `details` traz o URL.
            // Reaproveitar a exceção não é preguiça — é o que faz a app poder
            // usar a máquina de 3DS que já tem, sem um segundo caminho.
            throw new CreditCardValidationRequired($response['response']['details']);
        }

        if (! $this->isSuccess($response)) {
            throw new ApiError(
                $response['response']['message'] ?? 'Wallet payment failed.',
                $response['status'],
            );
        }

        return $response['response']['order'];
    }

    public function getEndpoint(): string
    {
        return 'payment/wallet';
    }
}
