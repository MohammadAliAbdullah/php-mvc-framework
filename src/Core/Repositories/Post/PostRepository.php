<?php

declare(strict_types=1);

namespace App\Core\Repositories\Post;

use PDO;
use App\Core\Models\Post\Post;
use App\Core\Repositories\Base\BaseRepository;



class PostRepository extends BaseRepository implements PostRepositoryInterface
{

    public function __construct(
        PDO $db
    )
    {
        parent::__construct($db, 'post', Post::class);
    }

    public function getLatestPosts(): array
    {
        return $this->model->limit(4)->findAll();
    }

}