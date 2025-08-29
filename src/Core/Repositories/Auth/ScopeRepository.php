<?php

declare(strict_types=1);

namespace App\Core\Repositories\Auth;

use App\Core\Models\UsersAuthScope;
use App\Core\Repositories\Base\BaseRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use PDO;

/**
 * Example scope repository with minimal scope definitions.
 */
class ScopeRepository extends BaseRepository implements ScopeRepositoryInterface
{
    // Example scopes, in real app you'd store them in DB
    private array $availableScopes = ['basic', 'email', 'profile'];

    public function __construct(PDO $db)
    {
        parent::__construct($db, 'clients', UsersAuthScope::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface
    {
        if (!in_array($identifier, $this->availableScopes, true)) {
            return null;
        }

        // Return a scope entity
        $scope = new class($identifier) implements ScopeEntityInterface {
            use EntityTrait;

            public function __construct(protected string $identifier)
            {
                $this->setIdentifier($identifier);
            }

            public function jsonSerialize()
            {
                return $this->getIdentifier();
            }
        };

        return $scope;
    }

    /**
     * {@inheritdoc}
     * @param array $scopes
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     * @param string|null $userIdentifier
     * @param string|null $authCodeId
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null): array
    {
        // Here, you can modify or remove scopes based on client, user, or grant type
        // For simplicity, we just return them unmodified.
        return $scopes;
    }
}
