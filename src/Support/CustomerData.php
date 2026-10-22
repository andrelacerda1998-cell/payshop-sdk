<?php

namespace RwInteractive\PayshopSdk\Support;

/**
 * Os dados do cliente que vão em cada transação para a autenticação EMV 3DS
 * (pedido do suporte da Payshop a 22/10/2026): c2 telefone e c3 email em
 * `extra_data.profile`, d1 IP em `customer_ip`.
 *
 * Tudo "sempre que possível": o que falta não se manda. Um convidado não tem
 * email e paga na mesma; nenhum campo destes é obrigatório para pagar.
 */
final class CustomerData
{
    /**
     * Os 9 algarismos de um número português, venha como vier:
     * "+351912345678", "+351-912345678", "00351 912 345 678" ou "912345678".
     * Um número estrangeiro (ou que não se perceba) devolve null.
     */
    public static function portuguesePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        if (strlen($digits) === 14 && str_starts_with($digits, '00351')) {
            $digits = substr($digits, 5);
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '351')) {
            $digits = substr($digits, 3);
        }

        return preg_match('/^[239]\d{8}$/', $digits) ? $digits : null;
    }

    public static function email(?string $raw): ?string
    {
        $email = trim((string) $raw);

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function ip(?string $raw): ?string
    {
        $ip = trim((string) $raw);

        return $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    /**
     * O `profile` de uma transação com cartão: só os campos que existem. O
     * telefone vai no mesmo formato do MB Way ({number, prefix: "351"}).
     */
    public static function threeDsProfile(?string $firstName, ?string $lastName, ?string $email, ?string $phone): array
    {
        $profile = array_filter([
            'first_name' => trim((string) $firstName),
            'last_name' => trim((string) $lastName),
        ], fn ($v) => $v !== '');

        if ($mail = self::email($email)) {
            $profile['email'] = $mail;
        }

        if ($number = self::portuguesePhone($phone)) {
            $profile['phone'] = ['number' => $number, 'prefix' => '351'];
        }

        return $profile;
    }
}
