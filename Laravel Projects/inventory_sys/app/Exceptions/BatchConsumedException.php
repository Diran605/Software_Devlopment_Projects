<?php

namespace App\Exceptions;

use Exception;

class BatchConsumedException extends Exception
{
    public function __construct(string $batchNumber)
    {
        parent::__construct(
            "Batch '{$batchNumber}' has already been partially or fully consumed and cannot be edited or deleted."
        );
    }
}
