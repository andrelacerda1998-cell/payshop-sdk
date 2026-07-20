<?php

namespace RwInteractive\PayshopSdk\Enums\Payment;

/**
 * Enum representing the status of a payment transaction.
 */
enum Status: string
{
    /**
     * @description Payment Order Initiated
     */
    case CREATED = 'CREATED';

    /**
     * @description Operation rejected by the black list
     */
    case BLACKLISTED = 'BLACKLISTED';

    /**
     * @description Pre-authorization cancelled by client
     */
    case CANCELLED = 'CANCELLED';

    /**
     * @description Transaction that has been too long on card hold
     */
    case EXPIRED = 'EXPIRED';

    /**
     * @description Transaction rejected by the anti-fraud system
     */
    case FRAUD = 'FRAUD';

    /**
     * @description Partially refunded transaction
     */
    case PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';

    /**
     * @description Pre-authorization partially confirmed
     */
    case PARTIALLY_CONFIRMED = 'PARTIALLY_CONFIRMED';

    /**
     * @description Pre-authorization that has placed a hold on the user's account and is waiting for the customer to confirm the hold
     */
    case PENDING_CONFIRMATION = 'PENDING_CONFIRMATION';

    /**
     * @description The transaction has been returned
     */
    case REFUNDED = 'REFUNDED';

    /**
     * @description Successfully completed transaction
     */
    case SUCCESS = 'SUCCESS';

    /**
     * @description Transaction rejected by bank
     */
    case REFUSED = 'REFUSED';

    /**
     * @description Transaction without 3DS that has been sent to the processor
     */
    case PENDING_PROCESSOR_RESPONSE = 'PENDING_PROCESSOR_RESPONSE';

    /**
     * @description User is on 3DS
     */
    case PENDING_3DS_RESPONSE = 'PENDING_3DS_RESPONSE';

    /**
     * @description User is on the payment card inserting his card
     */
    case PENDING_CARD = 'PENDING_CARD';

    /**
     * @description The user has canceled the payment from the payment card
     */
    case USER_CANCELLED = 'USER_CANCELLED';

    /**
     * @description Payment has been attempted but for security reasons the user must manually authorize the payment
     */
    case REDIRECTED_TO_3DS = 'REDIRECTED_TO_3DS';

    /**
     * @description The bank has required authentication for this transaction and is waiting for the user to access the 3DS
     */
    case AUTHENTICATION_REQUIRED = 'AUTHENTICATION_REQUIRED';

    /**
     * @description The user has provided all the necessary data to carry out the payment, and is awaiting confirmation from the payment provider
     */
    case PENDING_PAYMENT = 'PENDING_PAYMENT';

    /**
     * @description The transaction has arrived on 3DS but the user has abandoned the process, and has not been authenticated
     */
    case THREEDS_EXPIRED = 'THREEDS_EXPIRED';
}
