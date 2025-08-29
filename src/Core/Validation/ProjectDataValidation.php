<?php

declare(strict_types=1);

namespace App\Core\Validation;

class ProjectDataValidation
{
    public ?int $project_id = null;
    public int $site_id = 1;
    public int $status_id = 1;
    public ?int $customer_id = null;
    public ?string $name = null;
    public ?string $slug = null;
    public ?string $description = null;
    public ?string $location = null;
    public ?string $designer = null;
    public ?string $photographer = null;
    public ?string $status = null;
    public ?string $image = null;
    public ?string $image_thumb = null;
    public ?string $meta_title = null;
    public ?string $meta_description = null;
    public ?string $meta_keywords = null;
    public ?string $title = null;
    public ?string $label = null;
    public ?string $keyline_quote = null;
    public ?string $link_text = null;
    public bool $is_featured = false;
    public ?string $main_title = null;
    public ?string $main_description_one = null;
    public ?string $main_description_two = null;
    public ?string $main_description_three = null;
    public ?string $main_description_four = null;
    public ?string $main_image_one = null;
    public ?string $main_image_two = null;

    private bool $isValidData = true;
    private array $errors = [];
    private array $rawData = [];

    public function __construct(array $data)
    {
        $this->rawData = $data;
        
        // Initialize with proper type casting and validation
        $this->project_id = isset($data['project_id']) ? $this->validateInteger($data['project_id'], 'project_id') : null;
        $this->site_id = isset($data['site_id']) ? $this->validateInteger($data['site_id'], 'site_id', 1) : 1;
        $this->status_id = isset($data['status_id']) ? $this->validateInteger($data['status_id'], 'status_id', 1) : 1;
        $this->customer_id = isset($data['customer_id']) ? $this->validateInteger($data['customer_id'], 'customer_id') : null;
        
        // String validations with length limits
        $this->name = isset($data['name']) ? $this->validateString($data['name'], 'name', 191) : null;
        $this->slug = isset($data['slug']) ? $this->validateSlug($data['slug']) : null;
        $this->description = isset($data['description']) ? $this->validateText($data['description'], 'description') : null;
        $this->location = isset($data['location']) ? $this->validateString($data['location'], 'location', 191) : null;
        $this->designer = isset($data['designer']) ? $this->validateString($data['designer'], 'designer', 191) : null;
        $this->photographer = isset($data['photographer']) ? $this->validateString($data['photographer'], 'photographer', 191) : null;
        $this->status = isset($data['status']) ? $this->validateString($data['status'], 'status', 191) : null;
        
        // JSON validations
        $this->image = isset($data['image']) ? $this->validateJson($data['image'], 'image') : null;
        $this->image_thumb = isset($data['image_thumb']) ? $this->validateJson($data['image_thumb'], 'image_thumb') : null;
        
        // Meta fields
        $this->meta_title = isset($data['meta_title']) ? $this->validateString($data['meta_title'], 'meta_title', 191) : null;
        $this->meta_description = isset($data['meta_description']) ? $this->validateText($data['meta_description'], 'meta_description') : null;
        $this->meta_keywords = isset($data['meta_keywords']) ? $this->validateString($data['meta_keywords'], 'meta_keywords', 500) : null;
        
        // Additional fields
        $this->title = isset($data['title']) ? $this->validateString($data['title'], 'title', 191) : null;
        $this->label = isset($data['label']) ? $this->validateString($data['label'], 'label', 191) : null;
        $this->keyline_quote = isset($data['keyline_quote']) ? $this->validateString($data['keyline_quote'], 'keyline_quote', 255) : null;
        $this->link_text = isset($data['link_text']) ? $this->validateString($data['link_text'], 'link_text', 191) : null;
        $this->is_featured = isset($data['is_featured']) ? $this->validateBoolean($data['is_featured'], 'is_featured') : false;
        
        // Main content fields
        $this->main_title = isset($data['main_title']) ? $this->validateString($data['main_title'], 'main_title', 191) : null;
        $this->main_description_one = isset($data['main_description_one']) ? $this->validateText($data['main_description_one'], 'main_description_one') : null;
        $this->main_description_two = isset($data['main_description_two']) ? $this->validateText($data['main_description_two'], 'main_description_two') : null;
        $this->main_description_three = isset($data['main_description_three']) ? $this->validateText($data['main_description_three'], 'main_description_three') : null;
        $this->main_description_four = isset($data['main_description_four']) ? $this->validateText($data['main_description_four'], 'main_description_four') : null;
        $this->main_image_one = isset($data['main_image_one']) ? $this->validateString($data['main_image_one'], 'main_image_one', 191) : null;
        $this->main_image_two = isset($data['main_image_two']) ? $this->validateString($data['main_image_two'], 'main_image_two', 191) : null;

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
        if (empty($this->name) && empty($this->title)) {
            $this->addError('name', "either name or title is required");
        }

        if ($this->site_id <= 0) {
            $this->addError('site_id', "must be greater than 0");
        }

        if ($this->status_id <= 0) {
            $this->addError('status_id', "must be greater than 0");
        }

        if ($this->customer_id !== null && $this->customer_id <= 0) {
            $this->addError('customer_id', "must be greater than 0 when provided");
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
            'project_id' => $this->project_id,
            'site_id' => $this->site_id,
            'status_id' => $this->status_id,
            'customer_id' => $this->customer_id,
            'name' => $this->name ?? '',
            'slug' => $this->slug ?? '',
            'description' => $this->description ?? '',
            'location' => $this->location ?? '',
            'designer' => $this->designer ?? '',
            'photographer' => $this->photographer ?? '',
            'status' => $this->status ?? '',
            'image' => $this->image ?? '',
            'image_thumb' => $this->image_thumb ?? '',
            'meta_title' => $this->meta_title ?? '',
            'meta_description' => $this->meta_description ?? '',
            'meta_keywords' => $this->meta_keywords ?? '',
            'title' => $this->title ?? '',
            'label' => $this->label ?? '',
            'keyline_quote' => $this->keyline_quote ?? '',
            'link_text' => $this->link_text ?? '',
            'is_featured' => $this->is_featured ? 1 : 0,
            'main_title' => $this->main_title ?? '',
            'main_description_one' => $this->main_description_one ?? '',
            'main_description_two' => $this->main_description_two ?? '',
            'main_description_three' => $this->main_description_three ?? '',
            'main_description_four' => $this->main_description_four ?? '',
            'main_image_one' => $this->main_image_one ?? '',
            'main_image_two' => $this->main_image_two ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $data;
    }

    public function getUniqueIdentifier(): string
    {
        if ($this->project_id !== null) {
            return "project_id:{$this->project_id}";
        }
        if ($this->slug !== null) {
            return "slug:{$this->slug}";
        }
        if ($this->name !== null) {
            return "name:{$this->name}";
        }
        return "unknown:" . md5(serialize($this->rawData));
    }
}
