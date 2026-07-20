<?php

namespace RwInteractive\PayshopSdk\Enums\Payment;

enum OperationType:string
{
    case AUTHORIZATION = 'AUTHORIZATION';
    case DEFERRED = 'DEFERRED';
    case PAYOUT = 'PAYOUT';
    case TRANSFER = 'TRANSFER';
}
