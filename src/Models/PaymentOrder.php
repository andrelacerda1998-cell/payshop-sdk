<?php

namespace RwInteractive\PayshopSdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RwInteractive\PayshopSdk\Enums\Payment\OperationType;
use RwInteractive\PayshopSdk\Enums\Payment\Status;
use RwInteractive\PayshopSdk\Enums\PaymentMethods\PaymentMethodType;
use RwInteractive\PayshopSdk\PayshopPaymentOrder;
use RwInteractive\PayshopSdk\PayshopSdk;

class PaymentOrder extends Model
{
    use PayshopPaymentOrder;

    protected $table = 'payshop_payments_orders';

    protected $fillable = [
        'user_id',
        'uuid',
        'amount',
        'paid',
        'status',
        'refunded',
        'service',
        'service_uuid',
        'token',
        'ip',
        'payment_method_id',
        'type'
    ];

    protected function casts(): array
    {
        return [
            'type' => OperationType::class,
            'status' => Status::class
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(PayshopSdk::$customerModel);
    }
}
