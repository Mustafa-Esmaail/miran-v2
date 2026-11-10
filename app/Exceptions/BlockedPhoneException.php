<?php

namespace App\Exceptions;

use RuntimeException;

class BlockedPhoneException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This phone number has been blocked from submitting service requests.');
    }
}
