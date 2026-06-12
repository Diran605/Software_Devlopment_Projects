<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(int $itemId, int $qtyRequested, int $qtyAvailable = 0)
    {
        parent::__construct(
            "Insufficient stock for item ID {$itemId}. "
            . "Requested: {$qtyRequested}, Available: {$qtyAvailable}."
        );
    }
}
