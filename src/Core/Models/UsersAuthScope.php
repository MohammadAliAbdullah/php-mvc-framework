<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Models\Base\Model;

class UsersAuthScope extends Model
{
    private ?int $id = null;

    protected string $table = 'users_auth_scopes';
    
    public function __construct(
        ?int $id = null,
        ?int $userId = null,
        ?string $scopes = null,
        ?\DateTime $createdAt = null
    ) {
        parent::__construct();
        $this->id = $id;
    }
}
