<?php

declare(strict_types=1);

namespace App\Core\Repositories\Auth;

use App\Core\Models\Client;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface as LeagueClientRepositoryInterface;

/**
 * Your custom interface can extend the League one
 * so you can add custom methods if needed.
 */
interface ClientRepositoryInterface extends LeagueClientRepositoryInterface
{
    /**
     * Validate a token for a client.
     *
     * @param string $token The token to validate.
     * @return array|null Returns an array with token details if valid, null otherwise.
     */
    public function validateToken(string $token): ?array;

    public function createClient(array $data): Client;
    public function revokeClient(int $clientId): bool;
}
