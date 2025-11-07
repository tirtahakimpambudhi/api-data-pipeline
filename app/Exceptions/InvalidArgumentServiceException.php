<?php

namespace App\Exceptions;


class InvalidArgumentServiceException extends AppServiceException
{
    public function __construct(
        string $message = 'Failed to do something because invalid argument.',
        int $code = 400,
    ) {
        parent::__construct($message, $code);
    }

}
