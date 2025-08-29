<?php

declare(strict_types=1);

namespace App\Core\Validation;

class DesignResourceDataValidation
{
    public ?int $design_resource_id = null;
    public ?int $media_id = null;
    public ?string $img = null;
    public ?string $title = null;
    public ?string $brand = null;
    public ?string $description = null;
    public ?string $type = null;
    public ?string $resource_type = null;
    public bool $is_featured = false;
    public ?string $link_text = null;
    public ?string $grade = null;
    public ?string $slug = null;
    public ?string $img2 = null;

    private bool $isValidData = true;
    private array $errors = [];
    private array $rawData = [];

    public function __construct(array $data)
    {
        $this->rawData = $data;
        
        // Initialize with proper type casting and validation
        $this->design_resource_id = isset($data['design_resource_id']) ? $this->validateInteger($data['design_resource_id'], 'design_resource_id') : null;
        $this->media_id = isset($data['media_id']) ? $this->validateInteger($data['media_id'], 'media_id') : null;
        
        // JSON validations for images
        $this->img = isset($data['img']) ? $this->validateJson($data['img'], 'img') : null;
        $this->img2 = isset($data['img2']) ? $this->validateJson($data['img2'], 'img2') : null;
        
        // String validations with length limits
        $this->title = isset($data['title']) ? $this->validateString($data['title'], 'title', 191) : null;
        $this->brand = isset($data['brand']) ? $this->validateString($data['brand'], 'brand', 255) : null;
        $this->description = isset($data['description']) ? $this->validateText($data['description'], 'description') : null;
        $this->type = isset($data['type']) ? $this->validateString($data['type'], 'type', 191) : null;
        $this->resource_type = isset($data['resource_type']) ? $this->validateString($data['resource_type'], 'resource_type', 191) : null;
        $this->link_text = isset($data['link_text']) ? $this->validateString($data['link_text'], 'link_text', 191) : null;
        $this->grade = isset($data['grade']) ? $this->validateString($data['grade'], 'grade', 191) : null;
        $this->slug = isset($data['slug']) ? $this->validateSlug($data['slug']) : null;
        
        // Boolean validation
        $this->is_featured = isset($data['is_featured']) ? $this->validateBoolean($data['is_featured'], 'is_featured') : false;
    }

    private function validateInteger($value, string $field, ?int $default = null): ?int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (!is_numeric($value)) {
            $this->addError($field, "must be a valid integer");
            return $default;
        }

        $intValue = (int) $value;
        if ($intValue < 0) {
            $this->addError($field, "must be a positive integer");
            return $default;
        }

        return $intValue;
    }

    private function validateString($value, string $field, int $maxLength): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            $this->addError($field, "must be a string");
            return null;
        }

        $stringValue = trim($value);
        if (strlen($stringValue) > $maxLength) {
            $this->addError($field, "must not exceed {$maxLength} characters");
            return null;
        }

        return $stringValue;
    }

    private function validateText($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            $this->addError($field, "must be a string");
            return null;
        }

        return trim($value);
    }

    private function validateSlug($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            $this->addError('slug', "must be a string");
            return null;
        }

        $slug = trim($value);
        if (strlen($slug) > 191) {
            $this->addError('slug', "must not exceed 191 characters");
            return null;
        }

        // Basic slug validation - alphanumeric, hyphens, underscores
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $slug)) {
            $this->addError('slug', "must contain only letters, numbers, hyphens, and underscores");
            return null;
        }

        return strtolower($slug);
    }

    private function validateJson($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (!is_string($value)) {
            $this->addError($field, "must be a valid JSON string or array");
            return null;
        }

        // Test if it's valid JSON
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($field, "must be a valid JSON string");
            return null;
        }

        return $value;
    }

    private function validateBoolean($value, string $field): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $lowerValue = strtolower(trim($value));
            if (in_array($lowerValue, ['true', '1', 'yes', 'on'])) {
                return true;
            }
            if (in_array($lowerValue, ['false', '0', 'no', 'off'])) {
                return false;
            }
        }

        $this->addError($field, "must be a valid boolean value");
        return false;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
        $this->isValidData = false;
    }

    public function validate(): bool|array
    {
        // Additional business logic validations
        if (empty($this->title)) {
            $this->addError('title', "title is required");
        }

        if (!$this->isValidData) {
            return false;
        }

        return $this->toArray();
    }

    public function isValid(): bool
    {
        return $this->isValidData;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        // Always return a consistent structure with all fields
        $data = [
            'design_resource_id' => $this->design_resource_id,
            'media_id' => $this->media_id,
            'img' => $this->img ?? '',
            'title' => $this->title ?? '',
            'brand' => $this->brand ?? '',
            'description' => $this->description ?? '',
            'type' => $this->type ?? '',
            'resource_type' => $this->resource_type ?? '',
            'is_featured' => $this->is_featured ? 1 : 0,
            'link_text' => $this->link_text ?? '',
            'grade' => $this->grade ?? '',
            'slug' => $this->slug ?? '',
            'img2' => $this->img2 ?? ''
        ];

        return $data;
    }

    public function getUniqueIdentifier(): string
    {
        if ($this->design_resource_id !== null) {
            return "design_resource_id:{$this->design_resource_id}";
        }
        if ($this->slug !== null) {
            return "slug:{$this->slug}";
        }
        if ($this->title !== null) {
            return "title:{$this->title}";
        }
        return "unknown:" . md5(serialize($this->rawData));
    }
}
