<?php

namespace App\Exceptions;

class UnauthorizedServiceException extends AppServiceException
{
    public function __construct(
        string $message
    ) {
        parent::__construct($message, 401);
    }
}
