<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class InternalServerErrorHttpException extends HttpException
{
    public function __construct(
        string $message = '',
        ?\Throwable $previous = null,
        int $code = 0,
        array $headers = []
    ) {
        parent::__construct(500, $message, $headers, $previous, $code);
    }
}
