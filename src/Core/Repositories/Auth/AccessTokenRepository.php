<?php

declare(strict_types=1);

namespace App\Core\Repositories\Auth;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use PDO;

/**
 * AccessTokenRepository implements AccessTokenRepositoryInterface for
 * league/oauth2-server, storing tokens in a DB table named "access_tokens".
 */
class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken($clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
    {
        // Create an anonymous class implementing AccessTokenEntityInterface
        $token = new class implements AccessTokenEntityInterface {
            use AccessTokenTrait, EntityTrait, TokenEntityTrait;
        };

        // Set client, user ID, and scopes
        $token->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }
        $token->setUserIdentifier($userIdentifier);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $tokenId      = $accessTokenEntity->getIdentifier();            // e.g. "abc123"
        $clientId     = $accessTokenEntity->getClient()->getIdentifier(); // e.g. "client_id_123"
        $userId       = $accessTokenEntity->getUserIdentifier();        // e.g. "1" or null
        $expiry       = $accessTokenEntity->getExpiryDateTime();        // DateTimeImmutable
        $isRevoked    = 0; // new tokens are not revoked initially

        // Optional: store scopes in a column as JSON or separate table
        $scopes = [];
        foreach ($accessTokenEntity->getScopes() as $scope) {
            $scopes[] = $scope->getIdentifier();
        }
        $scopesJson = json_encode($scopes);

        // Insert into "access_tokens" table
        $sql = "
            INSERT INTO access_tokens (id, client_id, user_id, revoked, expires_at, scopes)
            VALUES (:id, :client_id, :user_id, :revoked, :expires_at, :scopes)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $tokenId);
        $stmt->bindValue(':client_id', $clientId);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':revoked', $isRevoked);
        $stmt->bindValue(':expires_at', $expiry->format('Y-m-d H:i:s'));
        $stmt->bindValue(':scopes', $scopesJson);

        $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId): void
    {
        // Mark the token as revoked in DB
        $sql = "
            UPDATE access_tokens
            SET revoked = 1
            WHERE id = :token_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':token_id', $tokenId);
        $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked($tokenId): bool
    {
        // Check if the token row is revoked
        $sql = "
            SELECT revoked
            FROM access_tokens
            WHERE id = :token_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':token_id', $tokenId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            // If not found, treat as revoked or handle differently
            return true;
        }

        return (bool)$row['revoked'];
    }

    /**
     * Validate an access token and retrieve its record.
     *
     * @param string $token The access token to validate.
     * @return array|null Returns the access token record as an associative array if valid, null otherwise.
     */
    public function validateAccessToken(string $token): ?array
    {
        $sql = "
            SELECT at.token, at.user_id, at.client_id, at.scopes, at.expires_at
            FROM access_tokens at
            WHERE at.token = :token AND at.expires_at > NOW()
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'token' => $row['token'],
            'user_id' => $row['user_id'],
            'client_id' => $row['client_id'],
            'scopes' => $row['scopes'], // Assuming scopes are stored as a string (e.g., JSON or comma-separated).
            'expires_at' => $row['expires_at'],
        ];
    }
}
