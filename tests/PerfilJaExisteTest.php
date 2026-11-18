<?php

/*
 * Serviço 433 (18/11/2026): o cliente não conseguia juntar um cartão. O perfil
 * dele já existia no Payshop, mas a app não tinha o token guardado; cada
 * tentativa criava o cliente (200), pedia o perfil (409 "Customer profile
 * already exists") e rebentava antes de devolver o token. Agora o 409
 * atualiza o perfil e devolve o token.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RwInteractive\PayshopSdk\Api\Customer;
use RwInteractive\PayshopSdk\Exceptions\Api\ApiError;

/** @return array{0: Client, 1: \ArrayObject} */
function perfilClienteFalso(array $respostas): array
{
    $pedidos = new \ArrayObject;
    $historico = [];
    $pilha = HandlerStack::create(new MockHandler($respostas));
    $pilha->push(Middleware::history($historico));
    $pilha->push(function (callable $seguinte) use ($pedidos) {
        return function (Request $pedido, array $opcoes) use ($seguinte, $pedidos) {
            $pedidos->append($pedido->getMethod().' '.$pedido->getUri()->getPath());

            return $seguinte($pedido, $opcoes);
        };
    });

    return [new Client(['handler' => $pilha]), $pedidos];
}

function perfilRespostaDoCliente(): Response
{
    return new Response(200, [], json_encode(['Customer' => ['token' => 'tok-cliente', 'ext_id' => 'piquet-1541']]));
}

it('com o perfil já criado no Payshop, atualiza-o e devolve o token', function () {
    [$cliente, $pedidos] = perfilClienteFalso([
        perfilRespostaDoCliente(),
        new Response(409, [], json_encode(['message' => 'Conflict', 'code' => 409, 'details' => 'Customer profile already exists'])),
        new Response(200, [], json_encode(['message' => 'OK'])),
    ]);

    $detalhes = (new Customer)->withClient($cliente)->create('Ana', 'Silva', 'ana@exemplo.pt', 'piquet-1541', '912345678');

    expect($detalhes['token'])->toBe('tok-cliente')
        ->and($pedidos->getArrayCopy())->toBe(['POST customer', 'POST customer/profile', 'PUT customer/profile']);
});

it('sem perfil, cria-o e não o atualiza', function () {
    [$cliente, $pedidos] = perfilClienteFalso([
        perfilRespostaDoCliente(),
        new Response(200, [], json_encode(['message' => 'OK'])),
    ]);

    $detalhes = (new Customer)->withClient($cliente)->create('Ana', 'Silva', 'ana@exemplo.pt', 'piquet-1541', '912345678');

    expect($detalhes['token'])->toBe('tok-cliente')
        ->and($pedidos->getArrayCopy())->toBe(['POST customer', 'POST customer/profile']);
});

it('outro erro ao criar o perfil continua a falhar', function () {
    [$cliente] = perfilClienteFalso([
        perfilRespostaDoCliente(),
        new Response(422, [], json_encode(['message' => 'Unprocessable', 'code' => 422])),
    ]);

    (new Customer)->withClient($cliente)->create('Ana', 'Silva', 'ana@exemplo.pt', 'piquet-1541', '912345678');
})->throws(ApiError::class);
