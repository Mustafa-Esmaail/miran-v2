<?php

namespace App\Exceptions;

use RuntimeException;

class TooManyServiceRequestsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Too many service requests submitted from this device. Please try again later.');
    }
}
