<?php

namespace App\Exceptions;

use Exception;

class EditBlockedException extends Exception
{
    public function __construct(string $reason = 'This record cannot be edited.')
    {
        parent::__construct($reason);
    }
}
