<?php

namespace App\Exceptions;

class InternalServiceException extends AppServiceException
{
    public function __construct(
        string $message = 'Something went wrong on service layer.',
    ) {
        parent::__construct($message, 500);
    }
}
