<?php

namespace App\Exceptions;
use Illuminate\Support\MessageBag;
class ValidationServiceException extends AppServiceException
{
    public function __construct(
        protected array $errors,
        string $message = 'Failed to validate request.',
    ) {
        parent::__construct($message);
    }

    public function toMessageBag(): MessageBag
    {
        return new MessageBag($this->errors);
    }
}
