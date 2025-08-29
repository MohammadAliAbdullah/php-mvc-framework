<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class BadRequestHttpException extends HttpException
{
    public function __construct(
        string $message = '',
        ?\Throwable $previous = null,
        int $code = 0,
        array $headers = []
    ) {
        parent::__construct(400, $message, $headers, $previous, $code);
    }
}
