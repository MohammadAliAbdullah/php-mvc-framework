<?php

declare(strict_types=1);

namespace App\Core\Repositories\Auth;

use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface as LeagueAccessTokenRepositoryInterface;

interface AccessTokenRepositoryInterface extends LeagueAccessTokenRepositoryInterface
{
    /**
     * Validate an access token and retrieve its record.
     *
     * @param string $token The access token to validate.
     * @return array|null Returns the access token record as an associative array if valid, null otherwise.
     */
    public function validateAccessToken(string $token): ?array;
}
