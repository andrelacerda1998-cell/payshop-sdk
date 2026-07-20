<?php

namespace RwInteractive\PayshopSdk\Api\Test;

use RwInteractive\PayshopSdk\Api\Concerns\APIRequest;

class ApiKeyTest
{
    use APIRequest;

    public function test()
    {
        return $this->get($this->getEndpoint());
    }

    public function getEndpoint(): string
    {
        return 'api-key/me';
    }
}
