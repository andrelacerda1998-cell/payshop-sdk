<?php

namespace RwInteractive\PayshopSdk;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use RwInteractive\PayshopSdk\Commands\PayshopSdkCommand;

class PayshopSdkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('payshop-sdk')
            #->hasMigration('add_customerId_to_users_table')
            #->hasMigration('create_payshop_payments_methods_table')
            #->hasMigration('create_payshop_payments_orders_table')
            ->hasMigration('add_phoneNumber_to_paymentMethods_table')
            ->hasConfigFile()
            ->hasCommand(PayshopSdkCommand::class);
    }
}
