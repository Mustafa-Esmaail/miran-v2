<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicatePurchaseException extends RuntimeException
{
    /** @var array<int, array{cartItemId: int, type: string}> */
    public readonly array $duplicateItems;

    /** @param array<int, array{cartItemId: int, type: string}> $duplicateItems */
    public function __construct(array $duplicateItems)
    {
        parent::__construct('Duplicate purchase is not allowed.');
        $this->duplicateItems = $duplicateItems;
    }
}
