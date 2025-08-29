<?php

namespace App\Core\ModelsFilters;

class PostFilter {
    public int $start = 0;
    public int $limit = 20;
    public ?string $search = null;
    public ?string $like = null;
    public ?string $username = null;
    public ?string $status = null;
    public ?string $taxonomyItemSlug = null;
    public ?array $postIds = null;
    public ?int $taxonomyItemId = null;
    public ?string $type = null;
    public ?int $siteId = null;
    public ?int $adminId = null;
    public ?int $languageId = null;
    public ?bool $includeCommentCount = false;
    public ?int $commentStatus = null;
    public ?int $year = null;
    public ?int $month = null;
    public ?int $categories = null;
    public ?int $tags = null;
    public ?string $taxonomy = null;
    public ?string $orderBy = null;
    public ?string $direction = null;

    public function __construct(array $data = []) {
        $this->start = $data['start'] ?? 0;
        $this->limit = $data['limit'] ?? 20;
        $this->search = $data['search'] ?? null;
        $this->like = $data['like'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->taxonomyItemSlug = $data['taxonomyItemSlug'] ?? null;
        $this->postIds = $data['postIds'] ?? null;
        $this->taxonomyItemId = $data['taxonomyItemId'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->siteId = $data['siteId'] ?? null;
        $this->adminId = $data['adminId'] ?? null;
        $this->languageId = $data['languageId'] ?? null;
        $this->includeCommentCount = $data['includeCommentCount'] ?? false;
        $this->commentStatus = $data['commentStatus'] ?? null;
        $this->year = $data['year'] ?? null;
        $this->month = $data['month'] ?? null;
        $this->categories = $data['categories'] ?? null;
        $this->tags = $data['tags'] ?? null;
        $this->taxonomy = $data['taxonomy'] ?? null;
        $this->orderBy = $data['orderBy'] ?? null;
        $this->direction = $data['direction'] ?? null;
    }
    
}