<?php

namespace App\Exceptions;

use App\Models\Services;

class ConflictServiceException extends AppServiceException
{
    public function __construct(
        string $message
    ) {
        parent::__construct($message, 409);
    }
}
