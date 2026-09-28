<?php

namespace RwInteractive\PayshopSdk\Enums\Payment;

/**
 * Carteiras aceites pelo `POST /payment/wallet`.
 *
 * Os valores são os que a API espera, em maiúsculas e sem separador — não são
 * uma escolha nossa. Não confundir com o `PaymentProvider`, que identifica um
 * SERVIÇO de pagamento configurado no painel (e que a ordem já traz): aqui só
 * se diz de que carteira veio o payload.
 */
enum Wallet: string
{
    case GOOGLE_PAY = 'GOOGLEPAY';
    case APPLE_PAY = 'APPLEPAY';

    /**
     * O Apple Pay conta como pagamento seguro e nunca pede 3DS; o Google Pay
     * pode pedir, e nesse caso a API responde 303 com o URL do desafio.
     */
    public function podeExigir3ds(): bool
    {
        return $this === self::GOOGLE_PAY;
    }
}
