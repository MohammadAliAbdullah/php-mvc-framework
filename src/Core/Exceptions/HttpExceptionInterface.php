<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

/**
 * All HTTP-specific exceptions should implement this interface.
 */
interface HttpExceptionInterface
{
    /**
     * Returns the HTTP status code (e.g. 404, 500).
     */
    public function getStatusCode(): int;

    /**
     * Returns an array of headers to be sent with the response.
     */
    public function getHeaders(): array;
}
