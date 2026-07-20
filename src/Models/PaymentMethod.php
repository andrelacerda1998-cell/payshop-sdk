<?php

namespace RwInteractive\PayshopSdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RwInteractive\PayshopSdk\Enums\PaymentMethods\PaymentMethodType;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $table = 'payshop_payment_methods';

    protected $fillable = [
        'type',
        'uuid',
        'token',
        'brand',
        'country',
        'holder',
        'bin',
        'last4',
        'expire_month',
        'expire_year',
        'brand_description',
        'phone_number'
    ];

    protected $hidden = [
        'uuid',
        'token',
        'country',
        'user_id'
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'name' => 'string',
            'type' => PaymentMethodType::class,
        ];
    }
}
