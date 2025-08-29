<?php

declare(strict_types=1);

namespace App\Core\Validation;

use stdClass;

class PostDataValidation
{
    private bool $isValidData = true;
    private array $errors = [];
    private array $rawData = [];

    public stdClass $post;
    public stdClass $content;

    public function __construct(array $data)
    {
        $this->post = new stdClass();
        $this->content = new stdClass();

        $this->rawData = $data;


        if(isset($data['post_id'])){
            // integers
            if(isset($data['post_id'])) $this->post->post_id =  $this->validateInteger($data['post_id'], 'post_id') ?? null;
            if(isset($data['title'])) $this->post->title =  $this->validateString($data['title'], 'title', 191, true) ?? null;
            if(isset($data['site_id'])) $this->post->site_id =  ($this->validateInteger($data['site_id'], 'site_id', 1) ?? 1) ?? 1;
            if(isset($data['admin_id'])) $this->post->admin_id =  $this->validateInteger($data['admin_id'], 'admin_id', 1) ?? 1;
            if(isset($data['parent'])) $this->post->parent =  $this->validateInteger($data['parent'], 'parent') ?? null;
            if(isset($data['sort_order'])) $this->post->sort_order =  ($this->validateInteger($data['sort_order'], 'sort_order', 0) ?? 0) ?? 0;
            if(isset($data['comment_count'])) $this->post->comment_count =  ($this->validateInteger($data['comment_count'], 'comment_count', 0) ?? 0) ?? 0;
            if(isset($data['views'])) $this->post->views =  ($this->validateInteger($data['views'], 'views', 0) ?? 0) ?? 0;

            // strings
            if(isset($data['status'])) $this->post->status =  $this->validateString($data['status'], 'status', 191) ?? 'draft';
            if(isset($data['comment_status'])) $this->post->comment_status =  $this->validateString($data['comment_status'], 'comment_status', 191, true) ?? 'open';
            if(isset($data['password'])) $this->post->password =  $this->validateString($data['password'], 'password', 191, true) ?? null;
            if(isset($data['type'])) $this->post->type =  $this->validateString($data['type'], 'type', 191, true) ?? 'post';
            if(isset($data['template'])) $this->post->template =  $this->validateString($data['template'], 'template', 191, true) ?? 'default';
            if(isset($data['description'])) $this->post->description =  $this->validateString($data['description'], 'description', 1000) ?? null;
            if(isset($data['description_one'])) $this->post->description_one =  $this->validateString($data['description_one'], 'description_one', 1000) ?? null;
            if(isset($data['description_two'])) $this->post->description_two =  $this->validateString($data['description_two'], 'description_two', 1000) ?? null;
            if(isset($data['description_three'])) $this->post->description_three =  $this->validateString($data['description_three'], 'description_three', 1000) ?? null;

            // JSON
            if(isset($data['image'])) $this->post->image = $this->validateJson($data['image'], 'image') ?? null;
            if(isset($data['feature_image'])) $this->post->feature_image = $this->validateJson($data['feature_image'], 'feature_image') ?? null;
            if(isset($data['feature_image_thumb'])) $this->post->feature_image_thumb = $this->validateJson($data['feature_image_thumb'], 'feature_image_thumb') ?? null;
            if(isset($data['image_banner'])) $this->post->image_banner = $this->validateJson($data['image_banner'], 'image_banner') ?? null;
            if(isset($data['image_thumb'])) $this->post->image_thumb = $this->validateJson($data['image_thumb'], 'image_thumb') ?? null;
            if(isset($data['main_image_one'])) $this->post->main_image_one = $this->validateJson($data['main_image_one'], 'main_image_one') ?? null;
            if(isset($data['main_image_two'])) $this->post->main_image_two = $this->validateJson($data['main_image_two'], 'main_image_two') ?? null;
            // booleans/flags as ints
            if(isset($data['is_featured'])) $this->post->is_featured =  ($this->validateInteger($data['is_featured'], 'is_featured', 0) ?? 0) ?? 0;

            // content strings
            if(isset($data['language_id'])) $this->content->language_id =  ($this->validateInteger($data['language_id'], 'language_id', 1) ?? 1) ?? 1;
            if(isset($data['name'])) $this->content->name =  $this->validateString($data['name'], 'name', 191) ?? null;
            if(isset($data['slug'])) $this->content->slug =  $this->validateSlug($data['slug']) ?? null;
            if(isset($data['excerpt'])) $this->content->excerpt =  $this->validateString($data['excerpt'], 'excerpt', 1000) ?? null;
            if(isset($data['meta_title'])) $this->content->meta_title =  $this->validateString($data['meta_title'], 'meta_title', 191) ?? null;
            if(isset($data['meta_description'])) $this->content->meta_description = $this->validateString($data['meta_description'], 'meta_description', 191, true) ?? null;
            if(isset($data['meta_keywords'])) $this->content->meta_keywords =  $this->validateString($data['meta_keywords'], 'meta_keywords', 191) ?? null;
            if(isset($data['link_text'])) $this->content->link_text =  $this->validateString($data['link_text'], 'link_text', 191) ?? null;

            if(isset($this->content->name) && !isset($this->post->title)) $this->post->title = $this->content->name;
        }else{
            // integers
        $this->post->post_id = isset($data['post_id']) ? $this->validateInteger($data['post_id'], 'post_id') : null;
        $this->post->site_id = isset($data['site_id']) ? ($this->validateInteger($data['site_id'], 'site_id', 1) ?? 1) : 1;
        $this->post->admin_id = isset($data['admin_id']) ? $this->validateInteger($data['admin_id'], 'admin_id', 1) : 1;
        $this->post->parent = isset($data['parent']) ? $this->validateInteger($data['parent'], 'parent') : null;
        $this->post->sort_order = isset($data['sort_order']) ? ($this->validateInteger($data['sort_order'], 'sort_order', 0) ?? 0) : 0;
        $this->post->comment_count = isset($data['comment_count']) ? ($this->validateInteger($data['comment_count'], 'comment_count', 0) ?? 0) : 0;
        $this->post->views = isset($data['views']) ? ($this->validateInteger($data['views'], 'views', 0) ?? 0) : 0;
        
        // strings
        $this->post->status = isset($data['status']) ? $this->validateString($data['status'], 'status', 191) : 'draft';
        $this->post->comment_status = isset($data['comment_status']) ? $this->validateString($data['comment_status'], 'comment_status', 191, true) : 'open';
        $this->post->password = isset($data['password']) ? $this->validateString($data['password'], 'password', 191, true) : null;
        $this->post->type = isset($data['type']) ? $this->validateString($data['type'], 'type', 191, true) : 'post';
        $this->post->template = isset($data['template']) ? $this->validateString($data['template'], 'template', 191, true) : 'default';
        $this->post->description = isset($data['description']) ? $this->validateString($data['description'], 'description', 1000) : null;
        $this->post->description_one = isset($data['description_one']) ? $this->validateString($data['description_one'], 'description_one', 1000) : null;
        $this->post->description_two = isset($data['description_two']) ? $this->validateString($data['description_two'], 'description_two', 1000) : null;
        $this->post->description_three = isset($data['description_three']) ? $this->validateString($data['description_three'], 'description_three', 1000) : null;
        
        // JSON
        $this->post->image = isset($data['image']) ? $this->validateJson($data['image'], 'image') : null;
        $this->post->feature_image = isset($data['feature_image']) ? $this->validateJson($data['feature_image'], 'feature_image') : null;
        $this->post->feature_image_thumb = isset($data['feature_image_thumb']) ? $this->validateJson($data['feature_image_thumb'], 'feature_image_thumb') : null;
        $this->post->image_banner = isset($data['image_banner']) ? $this->validateJson($data['image_banner'], 'image_banner') : null;
        $this->post->image_thumb = isset($data['image_thumb']) ? $this->validateJson($data['image_thumb'], 'image_thumb') : null;
        $this->post->main_image_one = isset($data['main_image_one']) ? $this->validateJson($data['main_image_one'], 'main_image_one') : null;
        $this->post->main_image_two = isset($data['main_image_two']) ? $this->validateJson($data['main_image_two'], 'main_image_two') : null;
        // booleans/flags as ints
        $this->post->is_featured = isset($data['is_featured']) ? ($this->validateInteger($data['is_featured'], 'is_featured', 0) ?? 0) : 0;
        
        // content strings
        $this->content->language_id = isset($data['language_id']) ? ($this->validateInteger($data['language_id'], 'language_id', 1) ?? 1) : 1;
        $this->content->name = isset($data['name']) ? $this->validateString($data['name'], 'name', 191) : null;
        $this->content->slug = isset($data['slug']) ? $this->validateSlug($data['slug']) : null;
        $this->content->excerpt = isset($data['excerpt']) ? $this->validateString($data['excerpt'], 'excerpt', 1000) : null;
        $this->content->meta_title = isset($data['meta_title']) ? $this->validateString($data['meta_title'], 'meta_title', 191) : null;
        $this->content->meta_description = isset($data['meta_description']) ? $this->validateString($data['meta_description'], 'meta_description', 191, true) : null;
        $this->content->meta_keywords = isset($data['meta_keywords']) ? $this->validateString($data['meta_keywords'], 'meta_keywords', 191) : null;
        $this->content->link_text = isset($data['link_text']) ? $this->validateString($data['link_text'], 'link_text', 191) : null;

        $this->post->title = $this->content->name;
        }

        
    }

    private function validateInteger($value, string $field, ?int $default = null, bool $isMandatory = false): ?int
    {
        if($isMandatory && ($value === null || $value === '') && !isset($this->rawData['post_id'])){
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
        if($isMandatory && ($value === null || $value === '') && !isset($this->rawData['post_id'])){
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
        if($isMandatory && ($value === null || $value === '') && !isset($this->rawData['post_id'])){
            $this->addError($field, 'is mandatory');
            return null;
        }
        if ($value === null || $value === '') { return null; }
        if (!is_string($value)) { $this->addError($field, 'must be a string'); return null; }
        return trim($value);
    }

    private function validateSlug($value): ?string
    {
        if ($value === null || $value === '') { return null; }
        if (!is_string($value)) { $this->addError('slug', 'must be a string'); return null; }
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9\-_]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return substr(trim($slug, '-'), 0, 191);
    }

    private function validateJson($imageValue, string $field): ?string
    {
        if ($imageValue === '' || $imageValue === null) { return '[]'; }
        $imageValue = is_string($imageValue) ? $imageValue : (is_array($imageValue) ? json_encode($imageValue) : (string)$imageValue);
        if ($this->isValidJson($imageValue)) { 
            $this->addError($field, 'must be a valid JSON string');
            return $imageValue; 
        }
        if (!str_contains($imageValue, '/media/Posts/')) { $imageValue = "/media/Posts/{$imageValue}"; }
        $data = [[ 'id'=>null,'file'=>['name'=>basename($imageValue),'size'=>0,'type'=>'image/jpeg','error'=>0,'tmp_name'=>$imageValue,'full_path'=>basename($imageValue)],'name'=>basename($imageValue),'size'=>0,'type'=>'image/jpeg','image'=>$imageValue,'status'=>['name'=>'Expected','severity'=>'info'],'media_id'=>null,'objectURL'=>$imageValue,'created_at'=>'','description'=>'','post_image_id'=>null,'project_image_id'=>null ]];
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

    // public function toArray(): array
    // {
    //     return [
    //         'post_id' => $this->post_id,
    //         'site_id' => $this->site_id,
    //         'admin_id' => $this->admin_id,
    //         'status' => $this->status ?? 'draft',
    //         'image' => $this->image ?? '[]',
    //         'feature_image' => $this->feature_image ?? '[]',
    //         'feature_image_thumb' => $this->feature_image_thumb ?? '[]',
    //         'image_banner' => $this->image_banner ?? '[]',
    //         'image_thumb' => $this->image_thumb ?? '[]',
    //         'main_image_one' => $this->main_image_one ?? '[]',
    //         'main_image_two' => $this->main_image_two ?? '[]',
    //         'comment_status' => $this->comment_status ?? 'open',
    //         'password' => $this->password ?? '',
    //         'parent' => $this->parent,
    //         'sort_order' => $this->sort_order,
    //         'type' => $this->type ?? 'post',
    //         'template' => $this->template ?? '',
    //         'comment_count' => $this->comment_count,
    //         'views' => $this->views,
    //         'description' => $this->description ?? '',
    //         'description_one' => $this->description_one,
    //         'description_two' => $this->description_two,
    //         'description_three' => $this->description_three,
    //         'is_featured' => $this->is_featured,
    //         'language_id' => $this->language_id,
    //         'name' => $this->name ?? '',
    //         'slug' => $this->slug ?? '',
    //         'excerpt' => $this->excerpt ?? '',
    //         'meta_title' => $this->meta_title ?? '',
    //         'meta_description' => $this->meta_description ?? '',
    //         'meta_keywords' => $this->meta_keywords ?? '',
    //         'link_text' => $this->link_text ?? ''
    //     ];
    // }
}


