<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class MethodNotAllowedHttpException extends HttpException
{
    /**
     * @param string[] $allowedMethods An array of allowed HTTP methods
     */
    public function __construct(
        array $allowedMethods,
        string $message = '',
        ?\Throwable $previous = null,
        int $code = 0,
        array $headers = []
    ) {
        $headers['Allow'] = strtoupper(implode(', ', $allowedMethods));
        parent::__construct(405, $message, $headers, $previous, $code);
    }
}
