<?php

namespace App\Exceptions;

class NotFoundServiceException extends AppServiceException
{
    public function __construct(
        string $message
    ) {
        parent::__construct($message, 404);
    }
}
