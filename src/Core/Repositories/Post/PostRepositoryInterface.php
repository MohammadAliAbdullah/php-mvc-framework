<?php

declare(strict_types=1);

namespace App\Core\Repositories\Post;

use App\Core\Repositories\Base\BaseRepositoryInterface;

interface PostRepositoryInterface extends BaseRepositoryInterface
{
    public function getLatestPosts(): array;
} 