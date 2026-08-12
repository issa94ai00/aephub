<?php

namespace App\Exceptions\Stock;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
