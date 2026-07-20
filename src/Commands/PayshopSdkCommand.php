<?php

namespace RwInteractive\PayshopSdk\Commands;

use Illuminate\Console\Command;
use RwInteractive\PayshopSdk\Api\Test\ApiKeyTest;
use RwInteractive\PayshopSdk\Enums\Services\PaymentProvider;
use RwInteractive\PayshopSdk\PayshopSdk;

class PayshopSdkCommand extends Command
{
    public $signature = 'payshop:test';

    public $description = 'Test Payshop Integration';

    public function handle(): int
    {
        try {
            $this->info('Testing API Key');
            $testService = ApiKeyTest::make();

            $testService->test();

            $this->info('Api key is valid!');

            $this->info('Testing Payment Providers');
            foreach (config('payshop-sdk.paymentServices') as $key => $service){
                if ($service === null){
                    throw new \Exception('Payment Service '.$key.' is not configured');
                }
            }

        }catch (\Exception $e){
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
