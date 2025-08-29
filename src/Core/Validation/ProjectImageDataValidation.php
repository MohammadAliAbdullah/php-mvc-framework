<?php

declare(strict_types=1);

namespace App\Core\Validation;

class ProjectImageDataValidation
{
    public ?int $project_image_id = null;
    public ?int $project_id = null;
    public ?string $image_link = null;
    public ?string $image = null;
    public int $sort_order = 0;
    public ?string $status = null;
    public ?string $way_points = null;

    private bool $isValidData = true;
    private array $errors = [];
    private array $rawData = [];

    public function __construct(array $data)
    {
        $this->rawData = $data;
        
        // Initialize with proper type casting and validation
        $this->project_image_id = isset($data['project_image_id']) ? $this->validateInteger($data['project_image_id'], 'project_image_id') : null;
        $this->project_id = isset($data['project_id']) ? $this->validateInteger($data['project_id'], 'project_id') : null;
        $this->image_link = isset($data['image_link']) ? $this->validateString($data['image_link'], 'image_link', 191) : null;
        $this->image = isset($data['image']) ? $this->validateJson($data['image'], 'image') : null;
        $this->sort_order = isset($data['sort_order']) ? $this->validateInteger($data['sort_order'], 'sort_order', 0) : 0;
        $this->status = isset($data['status']) ? $this->validateJson($data['status'], 'status') : null;
        $this->way_points = isset($data['way_points']) ? $this->validateJson($data['way_points'], 'way_points') : null;
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

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
        $this->isValidData = false;
    }

    public function validate(): bool|array
    {
        // Additional business logic validations
        $this->validateBusinessRules();

        if (!$this->isValidData) {
            return false;
        }

        return $this->toArray();
    }

    private function validateBusinessRules(): void
    {
        // Check required fields for import
        if (empty($this->project_id)) {
            $this->addError('project_id', "project_id is required");
        }

        if (empty($this->image_link)) {
            $this->addError('image_link', "image_link is required");
        }

        // Validate project_id is positive
        if ($this->project_id !== null && $this->project_id <= 0) {
            $this->addError('project_id', "must be greater than 0");
        }

        // Validate sort_order is non-negative
        if ($this->sort_order < 0) {
            $this->addError('sort_order', "must be 0 or greater");
        }
    }

    public function isValid(): bool
    {
        return $this->isValidData;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function getRawDataSafe(): array
    {
        // Safely retrieve raw data by checking isset for each property
        $safeData = [];
        
        if (isset($this->rawData['project_image_id'])) {
            $safeData['project_image_id'] = $this->rawData['project_image_id'];
        }
        if (isset($this->rawData['project_id'])) {
            $safeData['project_id'] = $this->rawData['project_id'];
        }
        if (isset($this->rawData['image_link'])) {
            $safeData['image_link'] = $this->rawData['image_link'];
        }
        if (isset($this->rawData['image'])) {
            $safeData['image'] = $this->rawData['image'];
        }
        if (isset($this->rawData['sort_order'])) {
            $safeData['sort_order'] = $this->rawData['sort_order'];
        }
        if (isset($this->rawData['status'])) {
            $safeData['status'] = $this->rawData['status'];
        }
        if (isset($this->rawData['way_points'])) {
            $safeData['way_points'] = $this->rawData['way_points'];
        }
        
        return $safeData;
    }

    public function toArray(): array
    {
        // Always return a consistent structure with all fields
        $data = [
            'project_image_id' => $this->project_image_id,
            'project_id' => $this->project_id,
            'image_link' => $this->image_link ?? '',
            'image' => $this->image ?? '',
            'sort_order' => $this->sort_order,
            'status' => $this->status ?? '',
            'way_points' => $this->way_points ?? ''
        ];

        return $data;
    }

    public function getUniqueIdentifier(): string
    {
        // For duplicate checking - use project_image_id if available, otherwise use project_id + image_link combination
        if ($this->project_image_id !== null) {
            return "project_image_id:{$this->project_image_id}";
        }
        if ($this->project_id !== null && $this->image_link !== null) {
            return "project_id:{$this->project_id}_image_link:{$this->image_link}";
        }
        return "unknown:" . md5(serialize($this->rawData));
    }
}
