<?php

declare(strict_types=1);

namespace App\Core\Validation;

use stdClass;

class TaxonomyItemDataValidation
{
    private bool $isValidData = true;
    private array $errors = [];
    private array $rawData = [];

    public stdClass $taxonomyItem;
    public stdClass $content;

    public function __construct(array $data)
    {
        $this->taxonomyItem = new stdClass();
        $this->content = new stdClass();

        $this->rawData = $data;

        //If taxonomy_item_id is set then use the existing taxonomy_item_id and update only the field exist in csv
        //  else create a new one and make sure all the requied fields will have proper value

        if(isset($data['taxonomy_item_id'])) $this->taxonomyItem->taxonomy_item_id =  $this->validateInteger($data['taxonomy_item_id'], 'taxonomy_item_id') ?? null;
        if(isset($data['category_name']))$this->taxonomyItem->name = $this->validateString($data['category_name'], 'name', 191) ?? '';
        if(isset($data['parent_category_id']) && $data['parent_category_id'] !== '' && $data['parent_category_id'] !== null){
            $this->taxonomyItem->parent_id =  $this->validateInteger($data['parent_category_id'], 'parent_category_id') ?? null;
        }
        if(isset($data['taxonomy_id'])) $this->taxonomyItem->taxonomy_id =  $this->validateInteger($data['taxonomy_id'], 'taxonomy_id', 1, true) ?? null;
        if(isset($data['parent_id'])) $this->taxonomyItem->parent_id =  ($this->validateInteger($data['parent_id'], 'parent_id') ?? null);
        if(isset($data['item_id'])) $this->taxonomyItem->item_id =  ($this->validateInteger($data['item_id'], 'item_id') ?? null);
        if(isset($data['sort_order'])) $this->taxonomyItem->sort_order =  ($this->validateInteger($data['sort_order'], 'sort_order', 0) ?? 0) ?? 0;
        if(isset($data['status'])) $this->taxonomyItem->status =  $this->validateInteger($data['status'], 'status', 0) ?? 0;
        
        // strings
        if(isset($data['template'])) $this->taxonomyItem->template =  $this->validateString($data['template'], 'template', 1000) ?? '';
        else $this->taxonomyItem->template = '';
        if(isset($data['color'])) $this->taxonomyItem->color =  $this->validateString($data['color'], 'color', 1000) ?? null;
        
        // JSON
        if(isset($data['image'])) $this->taxonomyItem->image = $this->validateJson($data['image'], 'image') ?? null;
         // booleans/flags as ints
        if(isset($data['is_featured'])) $this->taxonomyItem->is_featured =  ($this->validateInteger($data['is_featured'], 'is_featured', 0) ?? 0) ?? 0;

        // content strings
        if(isset($data['language_id'])) $this->content->language_id =  ($this->validateInteger($data['language_id'], 'language_id', 1) ?? 1) ?? 1;
        if(isset($data['name'])) $this->content->name =  $this->validateString($data['name'], 'name', 191) ?? '';
        if(isset($data['slug'])) $this->content->slug =  $this->generateSlugFromName($data['slug'], 'slug') ?? '';
        if(isset($data['content'])) $this->content->content =  $this->validateString($data['content'], 'content', 1000) ?? '';
        if(isset($data['meta_title'])) $this->content->meta_title =  $this->validateString($data['meta_title'], 'meta_title', 191) ?? '';
        if(isset($data['meta_description'])) $this->content->meta_description = $this->validateString($data['meta_description'], 'meta_description', 191, true) ?? '';
        if(isset($data['meta_keywords'])) $this->content->meta_keywords =  $this->validateString($data['meta_keywords'], 'meta_keywords', 191) ?? '';
        if(isset($data['link'])) $this->content->link =  $this->validateString($data['link'], 'link', 191) ?? '';
        
    }

    /**
     * Generate slug from name if not provided
     */
    private function generateSlugFromName(string $value, string $field): string
    {
        $value = $this->fixTextEncoding($value, $field);
        if (!$value) return '';
        return $this->validateSlug($value, $field) ?? '';
    }

    private function validateInteger($value, string $field, ?int $default = null, bool $isMandatory = false): ?int
    {
        $value = $this->fixTextEncoding($value, $field);
        if($isMandatory && ($value === null || $value === '') && !isset($this->rawData['taxonomy_item_id'])){
            $this->addError($field, 'is mandatory');
            return null;
        }
        if ($value === null || $value === '') { return $default; }
        if (!is_numeric($value)) { $this->addError($field, 'must be a valid integer'); return $default; }
        $int = (int)$value;
        if ($int < 0) { $this->addError($field, 'must be a positive integer'); return $default; }
        return $int;
    }

    private function validateString($value, string $field, int $maxLength, bool $isMandatory = false): ?string
    {
        $value = $this->fixTextEncoding($value, $field);
        if($isMandatory && ($value === null || $value === '') && !isset($this->rawData['taxonomy_item_id'])){
            $this->addError($field, 'is mandatory');
            return null;
        }
        if ($value === null || $value === '') { return null; }
        if (!is_string($value)) { $this->addError($field, 'must be a string'); return null; }
        $s = trim($value);
        //Instead of resizing the string pelase add error
        if (strlen($s) > $maxLength) { $s = substr($s, 0, $maxLength); }
        return $s;
    }

    private function validateText($value, string $field, bool $isMandatory = false): ?string
    {
        $value = $this->fixTextEncoding($value, $field);
        if($isMandatory && ($value === null || $value === '') && !isset($this->rawData['taxonomy_item_id'])){
            $this->addError($field, 'is mandatory');
            return null;
        }
        if ($value === null || $value === '') { return null; }
        if (!is_string($value)) { $this->addError($field, 'must be a string'); return null; }
        return trim($value);
    }

    private function validateSlug($value): ?string
    {
        $value = $this->fixTextEncoding($value, 'slug');
        if ($value === null || $value === '') { return null; }
        if (!is_string($value)) { $this->addError('slug', 'must be a string'); return null; }
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9\-_]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return substr(trim($slug, '-'), 0, 191);
    }

    private function validateJson($imageValue, string $field): ?string
    {
        $imageValue = $this->fixTextEncoding($imageValue, $field);
        if ($imageValue === '' || $imageValue === null) { return '[]'; }
        $imageValue = is_string($imageValue) ? $imageValue : (is_array($imageValue) ? json_encode($imageValue) : (string)$imageValue);
        if ($this->isValidJson($imageValue)) { 
            $this->addError($field, 'must be a valid JSON string');
            return $imageValue; 
        }
        if (!str_contains($imageValue, '/media/TaxonomyItems/')) { $imageValue = "/media/TaxonomyItems/{$imageValue}"; }
        $data = [[ 'id'=>null,'file'=>['name'=>basename($imageValue),'size'=>0,'type'=>'image/jpeg','error'=>0,'tmp_name'=>$imageValue,'full_path'=>basename($imageValue)],'name'=>basename($imageValue),'size'=>0,'type'=>'image/jpeg','image'=>$imageValue,'status'=>['name'=>'Expected','severity'=>'info'],'media_id'=>null,'objectURL'=>$imageValue,'created_at'=>'','description'=>'','taxonomy_item_image_id'=>null,'project_image_id'=>null ]];
        return json_encode($data) ?: '[]';

        return $value;
    }
    private function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
        $this->isValidData = false;
    }

    public function validate(): bool|self
    {
        if (!$this->isValidData) { return false; }
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Fix text encoding issues
     */
    private function fixTextEncoding(string|int|null $value, string $field): string|int|null
    {
        $textFields = ['category_name', 'sub_category_name', 'category_link', 'sub_category_link', 
        'meta_title', 'meta_description', 'meta_keywords', 'template', 'color', 'content'];
        if(in_array($field, $textFields)){
            if (isset($value) && is_string($value) && $value !== '') {
                if (mb_check_encoding($value, 'UTF-8')) {
                    $replacements = [
                        "\x92" => "'",
                        "\x93" => '"',
                        "\x94" => '"',
                        "\x96" => "–",
                        "\x97" => "—",
                        "\x85" => "…",
                        "\x91" => "'",
                        "\x82" => ",",
                        "\x84" => "„",
                        "\x8B" => "‹",
                        "\x9B" => "›",
                    ];
                    $value = strtr($value, $replacements);
                }
            }
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
        return $value;
    }

}


