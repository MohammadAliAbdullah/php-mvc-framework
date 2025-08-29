<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class ServiceUnavailableHttpException extends HttpException
{
    public function __construct(
        int $retryAfter = 0,
        string $message = '',
        ?\Throwable $previous = null,
        int $code = 0,
        array $headers = []
    ) {
        if ($retryAfter > 0) {
            $headers['Retry-After'] = (string) $retryAfter;
        }

        parent::__construct(503, $message, $headers, $previous, $code);
    }
}
