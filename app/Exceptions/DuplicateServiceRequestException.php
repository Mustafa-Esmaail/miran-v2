<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateServiceRequestException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A service request from this phone number or device was already submitted. Please try again after 12 hours.');
    }
}
