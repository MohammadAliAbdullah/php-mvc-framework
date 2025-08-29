<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Models\Base\Model;

class AccessToken extends Model
{
    public function __construct(
        public int $id,
        public int $clientId,
        public ?int $userId,
        public string $token,
        public bool $revoked,
        public \DateTime $expiresAt,
        public array $scopes = []
    ) {
        parent::__construct();
    }
}
