<?php

namespace RwInteractive\PayshopSdk\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

class InvalidCustomer extends Exception
{
    /**
     * Create a new InvalidCustomer instance.
     *
     * @param  Model  $owner
     * @return static
     */
    public static function notYetCreated($owner)
    {
        return new static(class_basename($owner).' is not a PayShop customer yet. See the createAsPayShopCustomer method.');
    }
}
