<?php

namespace App\Exceptions;

class PermissionDeniedServiceException extends AppServiceException
{
    public function __construct(
        string $message
    ) {
        parent::__construct($message, 403);
    }
}
