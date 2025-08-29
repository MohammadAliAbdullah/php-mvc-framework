<?php

declare(strict_types=1);

namespace App\Core\Models\Post;

use App\Core\Models\Base\Model;

class Post extends Model
{
    // Core post properties
    public ?int $post_id;
    public ?int $admin_id;
    public string|int|null $site_id;
    public ?string $status;
    public ?string $image;
    public ?string $comment_status;
    public ?string $password;
    public ?int $parent;
    public ?int $sort_order;
    public ?string $type;
    public ?string $template;
    public ?int $comment_count;
    public ?int $views;
    public ?string $created_at;
    public ?string $updated_at;
    public ?string $description;
    public ?string $description_one;
    public ?string $description_two;
    public ?string $description_three;
    public ?string $keyline_quote;
    public ?string $feature_image_thumb;
    public ?string $feature_image;
    public ?string $image_banner;
    public ?string $image_thumb;
    public ?string $main_image_one;
    public ?string $main_image_two;
    public ?int $is_featured;

    // Admin properties (from admin table join)
    public ?string $admin_display_name;
    public ?string $admin_username;
    public ?string $admin_first_name;
    public ?string $admin_last_name;
    public array $associations = [
        "admin",
        "site",
        "taxonomy_item",
        "post_content",
        "post_meta",
        "comment"  
    ];

    protected ?string $thumbnailUrl = null;
    protected ?string $authorName = null;


    public function __construct() 
    {
        parent::__construct();
    }


    
}

