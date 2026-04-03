<?php

namespace App\Exceptions;

use RuntimeException;

class BusinessException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        string $message,
        protected int $status = 422,
        protected array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
