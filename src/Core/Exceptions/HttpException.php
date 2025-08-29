<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException implements HttpExceptionInterface
{
    protected int $statusCode;
    protected array $headers;

    public function __construct(
        int $statusCode,
        string $message = '',
        array $headers = [],
        ?\Throwable $previous = null,
        int $code = 0
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set response headers.
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Set a single header.
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }
}
