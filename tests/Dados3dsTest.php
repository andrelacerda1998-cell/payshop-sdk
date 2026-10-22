<?php

/*
 * Os dados do cliente para a autenticação EMV 3DS (pedido da Payshop a
 * 22/10/2026): d1 IP em `customer_ip`, c2 telefone e c3 email em
 * `extra_data.profile`. Sempre que possível: o que falta não se manda.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Route;
use RwInteractive\PayshopSdk\Api\Customer;
use RwInteractive\PayshopSdk\Api\Payments\PaymentOrder as PaymentOrderApi;
use RwInteractive\PayshopSdk\Enums\Payment\OperationType;
use RwInteractive\PayshopSdk\Models\PaymentMethod;
use RwInteractive\PayshopSdk\Models\PaymentOrder;
use RwInteractive\PayshopSdk\Support\CustomerData;

/** Um cliente HTTP que responde $respostas e guarda o que se pediu em $pedidos. */
function clienteFalso(array &$pedidos, array $respostas): Client
{
    $pilha = HandlerStack::create(new MockHandler($respostas));
    $pilha->push(Middleware::history($pedidos));

    return new Client(['handler' => $pilha]);
}

function corpo(array $pedidos, int $i = 0): array
{
    return json_decode((string) $pedidos[$i]['request']->getBody(), true);
}

beforeEach(function () {
    Route::get('/pago', fn () => 'ok')->name('pago');
    Route::get('/falhou', fn () => 'ko')->name('falhou');
    config()->set('payshop-sdk.url.success', 'pago');
    config()->set('payshop-sdk.url.failure', 'falhou');
});

it('o telefone português fica nos 9 algarismos, venha como vier', function (?string $bruto, ?string $esperado) {
    expect(CustomerData::portuguesePhone($bruto))->toBe($esperado);
})->with([
    ['+351912345678', '912345678'],
    ['+351-912345678', '912345678'],
    ['00351 912 345 678', '912345678'],
    ['912345678', '912345678'],
    ['213456789', '213456789'],
    ['+44 7700 900123', null],
    ['', null],
    [null, null],
]);

it('o perfil 3DS só leva o que existe', function () {
    expect(CustomerData::threeDsProfile('Ana', 'Silva', 'ana@exemplo.pt', '+351912345678'))->toBe([
        'first_name' => 'Ana', 'last_name' => 'Silva', 'email' => 'ana@exemplo.pt',
        'phone' => ['number' => '912345678', 'prefix' => '351'],
    ]);
    // Um convidado: sem email, e o telemóvel confirmado por SMS.
    expect(CustomerData::threeDsProfile('Cliente', '5678', null, '+351912345678'))->toBe([
        'first_name' => 'Cliente', 'last_name' => '5678',
        'phone' => ['number' => '912345678', 'prefix' => '351'],
    ]);
    expect(CustomerData::threeDsProfile('', '', 'isto-nao-e-email', 'x'))->toBe([]);
});

it('a ordem com cartão leva o perfil em extra_data', function () {
    $pedidos = [];
    $api = PaymentOrderApi::make()->withClient(clienteFalso($pedidos, [
        new Response(200, [], json_encode(['order' => ['uuid' => 'o-1']])),
    ]));

    $api->createWithCreditCard(OperationType::DEFERRED, 1500, 'Pedido', now()->addDay(), 'c-1', [], [
        'profile' => CustomerData::threeDsProfile('Ana', 'Silva', 'ana@exemplo.pt', '+351912345678'),
    ]);

    expect(corpo($pedidos)['extra_data']['profile'])->toMatchArray([
        'email' => 'ana@exemplo.pt',
        'phone' => ['number' => '912345678', 'prefix' => '351'],
    ]);
});

it('o MB Way leva o email quando o há, e o número do MB Way tal e qual', function () {
    $pedidos = [];
    $api = PaymentOrderApi::make()->withClient(clienteFalso($pedidos, [
        new Response(200, [], json_encode(['order' => ['uuid' => 'o-1']])),
        new Response(200, [], json_encode(['order' => ['uuid' => 'o-2']])),
    ]));

    $api->createWithMbWay(OperationType::DEFERRED, 1500, 'Pedido', now()->addDay(), 'c-1', [], '912345678', 'Ana', 'Silva', [], 'ana@exemplo.pt');
    $api->createWithMbWay(OperationType::DEFERRED, 1500, 'Pedido', now()->addDay(), 'c-1', [], '912345678', 'Ana', 'Silva');

    expect(corpo($pedidos, 0)['extra_data']['profile'])->toMatchArray([
        'email' => 'ana@exemplo.pt', 'phone' => ['number' => '912345678', 'prefix' => '351'],
    ]);
    expect(corpo($pedidos, 1)['extra_data']['profile'])->not->toHaveKey('email');
});

it('o pagamento com cartão guardado leva o IP do cliente, quando é válido', function (?string $ip, bool $vai) {
    $pedidos = [];
    $api = PaymentOrderApi::make()->withClient(clienteFalso($pedidos, [
        new Response(200, [], json_encode(['order' => ['uuid' => 'o-1', 'status' => 'PENDING_CONFIRMATION']])),
    ]));

    $api->authorize(new PaymentMethod(['uuid' => 'cartao-1']), new PaymentOrder(['uuid' => 'o-1', 'amount' => 1500]), null, $ip);

    $enviado = corpo($pedidos);
    expect($enviado)->toMatchArray(['order_uuid' => 'o-1', 'card_uuid' => 'cartao-1']);
    $vai ? expect($enviado['customer_ip'])->toBe($ip) : expect($enviado)->not->toHaveKey('customer_ip');
})->with([
    ['83.240.10.7', true],
    ['2001:db8::1', true],
    [null, false],
    ['', false],
    ['não é um ip', false],
]);

it('o perfil do cliente deixa de mandar o 351 duas vezes', function () {
    $pedidos = [];
    $api = Customer::make()->withClient(clienteFalso($pedidos, [new Response(200, [], '{}')]));

    $api->updateProfile('Ana', 'Silva', 'ana@exemplo.pt', 'c-1', '+351912345678');

    expect(corpo($pedidos)['mobile_phone'])->toBe(['prefix' => '+351', 'number' => '912345678']);
});
