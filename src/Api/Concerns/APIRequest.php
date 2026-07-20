<?php

namespace RwInteractive\PayshopSdk\Api\Concerns;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use RwInteractive\PayshopSdk\Exceptions\Api\ApiError;
use RwInteractive\PayshopSdk\Exceptions\Api\ApiMethodInvalid;
use RwInteractive\PayshopSdk\Exceptions\Api\InvalidAuthentication;
use RwInteractive\PayshopSdk\PayshopSdk;

trait APIRequest
{
    public Client $apiClient;

    public static function make()
    {
        $instance = new static;
        $instance->init();
        return $instance;
    }

    public function init()
    {
        $this->apiClient = new Client([
            'base_uri' => self::baseUrl(),
            // Evita esperas indefinidas em falhas de rede (o padrão do Guzzle é 0 = infinito).
            'timeout' => (float) config('payshop-sdk.timeout', 15),
            'connect_timeout' => (float) config('payshop-sdk.connect_timeout', 5),
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(self::getApiToken()),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }

    abstract public function getEndpoint(): string;

    public static function getApiToken()
    {
        return config('payshop-sdk.api.key');
    }
    public static function getSignature()
    {
        return config('payshop-sdk.api.signature');
    }

    /**
     * @return string the base URL for the given class
     */
    public static function baseUrl()
    {
        return PayshopSdk::getApiBaseUrl();
    }


    /**
     * @throws ApiMethodInvalid|ApiError|InvalidAuthentication
     */
    public function post($endpoint, $data = [], $throwable = true): array
    {
        return $this->sendRequest('post', $endpoint, $data, throwable: $throwable);

    }

    /**
     * @throws ApiMethodInvalid|ApiError|InvalidAuthentication
     */
    public function get($endpoint, $data = [], $throwable=true): array
    {
        return $this->sendRequest('get', $endpoint, query: $data, throwable: $throwable);
    }

    /**
     * @throws ApiMethodInvalid|ApiError|InvalidAuthentication
     */
    public function delete($endpoint, $data = [], $throwable=true): array
    {
        return $this->sendRequest('delete', $endpoint, query: $data, throwable: $throwable);
    }

    /**
     * @throws ApiMethodInvalid|ApiError|InvalidAuthentication
     */
    public function put($endpoint, $data = [], $throwable=false): array
    {
        return $this->sendRequest('put', $endpoint, data: $data, throwable: $throwable);
    }

    /**
     * @throws ApiMethodInvalid|ApiError|InvalidAuthentication
     */
    private function sendRequest(string $method, string $url, array $data = [], $query = [], $throwable = true): array
    {
        if (method_exists($this->apiClient, $method)) {
            try {
                if ($method === 'post' || $method === 'put'){
                    $data['signature'] = self::getSignature();

                    if (isset($data['customer_ext_id'])){
                        $data['customer_ext_id'] = (string)$data['customer_ext_id'];
                    }
                }

                $requestData = ['json' => $data, 'query' => $query];

                $this->logRequestStart($method, $url, $requestData);
                $response = $this->apiClient->{$method}($url, $requestData);
                $data = $response->getBody()->getContents();
                $this->logRequestEnd($method, $url, $data, microtime(true));

                return $this->processResponse($data, $response);
            } catch (ClientException $e) {
                $this->logError($method, $url, $e);
                $response = $e->getResponse();
                if ($response->getStatusCode() === 401){
                    throw new InvalidAuthentication();
                }
                if (!$throwable) {
                    $data = $response->getBody()->getContents();
                    return $this->processResponse($data, $response);
                }else{
                    $body = (string) $response->getBody();
                    throw new ApiError($body !== '' ? $body : 'Something went wrong in API.', $response->getStatusCode(), $e);
                }
            }
        }

        throw new ApiMethodInvalid();
    }

    private function logRequestStart(string $method, string $url, array $requestData): void
    {
        if ($this->shouldLog()) {
            Log::channel('requests')->info("$method $url", self::scrubSensitive($requestData));
        }
    }
    private function logRequestEnd(string $method, string $url, string $response, float $startTime): void
    {
        if ($this->shouldLog()) {
            $duration = microtime(true) - $startTime;
            Log::channel('requests')->info("$method $url {$duration}s", ['response' => self::scrubResponse($response)]);
        }
    }

    /**
     * Chaves sensíveis que nunca devem aparecer em claro nos logs (dados de cartão e segredos).
     */
    private const SENSITIVE_KEYS = [
        'pan', 'card_number', 'card', 'number', 'cvv', 'cvc', 'cvc2', 'cvv2',
        'card_pan', 'card_cvv', 'card_expiry_month', 'card_expiry_year',
        'expiry', 'expiration', 'exp_month', 'exp_year', 'holder', 'card_holder',
        'signature', 'validation_hash', 'api_key', 'apikey', 'token', 'authorization', 'password',
    ];

    /**
     * Redige recursivamente valores de chaves sensíveis num array (case-insensitive).
     */
    private static function scrubSensitive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::scrubSensitive($value);
            } elseif (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Redige o corpo da resposta (string JSON) antes de o registar; se não for JSON, devolve intacto.
     */
    private static function scrubResponse(string $response): string
    {
        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            return $response;
        }

        return json_encode(self::scrubSensitive($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function logError(string $method, string $url, ClientException $e): void
    {
        if ($this->shouldLog()) {
            Log::channel('requests')->error("$method $url falhou: HTTP ".$e->getCode(), [
                'status' => $e->getResponse()?->getStatusCode(),
            ]);
        }
    }

    private function shouldLog(): bool
    {
        return array_key_exists('requests', config('logging.channels'));
    }

    /**
     * @throws InvalidAuthentication
     */
    private function processResponse(string $data, Response $response): array
    {
        $responseData = json_decode($data, true);
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300){
            self::validateRequest($responseData);
        }

        return ['response' => $responseData, 'status' => $response->getStatusCode()];
    }

    /**
     * @throws InvalidAuthentication
     */
    public static function validateRequest(array $response): void
    {
        if (!isset($response['validation_hash'])){
            // Fail-closed opcional: com strict_signature ativo, uma resposta sem validation_hash
            // é rejeitada em vez de aceite silenciosamente. Default desligado para não quebrar
            // respostas que o Payshop legitimamente não assine — ativar só após validar em sandbox.
            if (config('payshop-sdk.strict_signature', false)) {
                throw new InvalidAuthentication();
            }

            Log::warning('Payshop: resposta sem validation_hash — assinatura não verificada (strict_signature desligado).');

            return;
        }
        $keys = array_keys($response);
        $keys = array_flip($keys);
        unset($keys['message'], $keys['code'], $keys['current_time'], $keys['validation_hash']);

        $signature = self::getSignature();

        $data = [];

        foreach ($keys as $key => $value){
            $data[$key] = $response[$key];
        }

        $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $validationHash = hash('sha256', $data.$signature);

        if ($response['validation_hash'] !== $validationHash){
            throw new InvalidAuthentication();
        }
    }

    public function isProduction(): bool
    {
        return config('payshop-sdk.environment') == 'production';
    }

    public function isSuccess($response):bool
    {
        return $response['status']>=200 && $response['status']<300;
    }
}
