<?php

declare(strict_types=1);

namespace App\Core\Repositories\Auth;

use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface as LeagueRefreshTokenRepositoryInterface;

interface RefreshTokenRepositoryInterface extends LeagueRefreshTokenRepositoryInterface
{
    // Additional methods if needed
}
