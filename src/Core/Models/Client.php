<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Models\Base\Model;

class Client extends Model
{
    public int $id;
    public string $secret;
    public string $name;
    public string $scopes;
    public string $redirectUri;
    public bool $revoked;
    public bool $isConfidential;
    public \DateTime $createdAt;
    public function __construct(
    ) {
        parent::__construct();
    }
}
