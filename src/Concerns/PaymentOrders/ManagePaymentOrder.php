<?php

namespace RwInteractive\PayshopSdk\Concerns\PaymentOrders;

use RwInteractive\PayshopSdk\Enums\Payment\Status;
use RwInteractive\PayshopSdk\Exceptions\Api\OperationInvalid;
use RwInteractive\PayshopSdk\Models\PaymentMethod;
use RwInteractive\PayshopSdk\PayshopSdk;

trait ManagePaymentOrder
{
    /**
     * @throws OperationInvalid
     */
    public function authorize(
        PaymentMethod $paymentMethod,
        ?int          $amount = null
    ): static
    {
        if ($this->status === Status::CREATED) {
            $response = PayshopSdk::paymentOrders()->orders->authorize($paymentMethod, $this, $amount);

            $this->status = $response['order']['status'];

            $this->payment_method_id = $paymentMethod->id;
            $this->save();
            return $this;
        }

        throw new OperationInvalid();
    }

    public function confirm(?int $amount = null)
    {
        //if ($this->status === Status::PENDING_CONFIRMATION || $this->status === Status::CREATED) {
            $response = PayshopSdk::paymentOrders()->orders->confirmation($this, $amount);

            $this->status = $response['order']['status'];
            $this->save();
            return $this;
        //}

        // throw new OperationInvalid();
    }

    public function refund(?int $amount = null)
    {
       # if ($this->status === Status::SUCCESS) {
            $response = PayshopSdk::paymentOrders()->orders->refund($this, $amount);

            $this->status = $response['order']['status'];
            $this->save();
            return $this;
        #}

        #throw new OperationInvalid();
    }

    public function updateData()
    {
        $response = PayshopSdk::paymentOrders()->orders->details($this);
        $this->status = $response['order']['status'];
        $this->save();
        return $this;
    }

    public function cancel()
    {
        #if ($this->status === Status::PENDING_CONFIRMATION) {
            $response = PayshopSdk::paymentOrders()->orders->cancel($this);
            $this->status = $response['order']['status'];
            $this->save();
            return $this;
        #}

        throw new OperationInvalid();
    }

    public function process()
    {
        $response = PayshopSdk::paymentOrders()->orders->process($this);
        $this->status = $response['order']['status'];
        $this->save();
        return $this;
    }
}
