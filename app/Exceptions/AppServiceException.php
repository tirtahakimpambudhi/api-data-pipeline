<?php

namespace App\Exceptions;

use Illuminate\Support\MessageBag;
use Throwable;

abstract class AppServiceException extends \Exception
{
    public function __construct(
        string $message = 'Something went wrong on service layer.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function toMessageBag(): MessageBag
    {
        return new MessageBag(['errors' => [$this->getMessage()]]);
    }

    public function getBags(): MessageBag
    {
        return new MessageBag(['errors' => [$this->getMessage()]]);
    }
}
