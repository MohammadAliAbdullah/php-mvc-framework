<?php

namespace App\Core\Exceptions;

use Exception;

class ValidationException extends HttpException
{
    /**
     * The validation errors.
     *
     * @var array<string, array<string>>
     */
    protected array $errors;

    /**
     * Create a new ValidationException instance.
     *
     * @param array<string, array<string>> $errors Validation errors.
     * @param string $message Exception message.
     * @param int $code HTTP status code, default to 422 (Unprocessable Entity).
     */
    public function __construct(array $errors, string $message = "Validation failed.", int $code = 422)
    {
        parent::__construct($code, $message);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Convert the exception to a JSON response.
     *
     * @return string JSON-encoded error response.
     */
    public function toJson(): string
    {
        return json_encode([
            'message' => $this->getMessage(),
            'errors' => $this->getErrors(),
        ]);
    }
}
