<?php

namespace RwInteractive\PayshopSdk\Concerns\Customer;

use RwInteractive\PayshopSdk\Exceptions\CustomerAlreadyCreated;
use RwInteractive\PayshopSdk\Exceptions\InvalidCustomer;
use RwInteractive\PayshopSdk\PayshopSdk;

trait ManagesCustomer
{
    /**
     * Retrieve the Payshop customer ID.
     *
     * @return string|null
     */
    public function payShopId(): ?string
    {
        return $this->pay_shop_id;
    }

    public function hasPayShopId(): bool
    {
        return ! is_null($this->pay_shop_id);
    }

    /**
     * Determine if the customer has a PayShop customer ID and throw an exception if not.
     *
     * @return void
     *
     * @throws InvalidCustomer
     */
    protected function assertCustomerExists(): void
    {
        if (! $this->hasPayShopId()) {
            throw InvalidCustomer::notYetCreated($this);
        }
    }

    /**
     * Create a Stripe customer for the given model.
     *
     * @return array
     *
     * @throws CustomerAlreadyCreated
     */
    public function createAsPayShopCustomer()
    {
        if ($this->hasPayShopId()) {
            throw CustomerAlreadyCreated::exists($this);
        }

        $customer = PayshopSdk::customers()->create(
            $this->getFirstName(),
            $this->getLastName(),
            $this->getEmail(),
            $this->getCustomerKey(),
            $this->getPhoneNumber(),
        );

        $this->pay_shop_id = $customer['token'];

        $this->save();

        return $customer;
    }

    abstract public function getFirstName();

    abstract public function getLastName();
    abstract public function getEmail();
    abstract public function getPhoneNumber();

}
