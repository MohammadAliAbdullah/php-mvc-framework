<?php

declare(strict_types=1);

namespace App\Core\Repositories\Auth;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;
use PDO;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Pass a PDO instance (or other DB connection) via constructor.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Creates a new (in-memory) refresh token entity that implements RefreshTokenEntityInterface.
     */
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new class implements RefreshTokenEntityInterface {
            use RefreshTokenTrait, EntityTrait;
        };
    }

    /**
     * Persist the new refresh token to the database.
     *
     * The $refreshTokenEntity will have:
     *  - a unique identifier (token ID)        -> getIdentifier()
     *  - an expiry (DateTime)                  -> getExpiryDateTime()
     *  - the associated access token ID        -> getAccessToken()->getIdentifier()
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $tokenId       = $refreshTokenEntity->getIdentifier();                   // string
        $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(); // string
        $expiry        = $refreshTokenEntity->getExpiryDateTime();              // DateTimeInterface

        // Save to refresh_tokens table (example schema)
        //  columns: id (PK), access_token_id, revoked (tinyint), expires_at (datetime)
        $sql = "INSERT INTO refresh_tokens (id, access_token_id, revoked, expires_at)
                VALUES (:id, :access_token_id, 0, :expires_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $tokenId);
        $stmt->bindValue(':access_token_id', $accessTokenId);
        $stmt->bindValue(':expires_at', $expiry->format('Y-m-d H:i:s'));
        $stmt->execute();
    }

    /**
     * Mark an existing refresh token as revoked (so it can't be used again).
     */
    public function revokeRefreshToken($tokenId): void
    {
        $sql  = "UPDATE refresh_tokens
                 SET revoked = 1
                 WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $tokenId);
        $stmt->execute();
    }

    /**
     * Check if a refresh token has been revoked.
     * Return true if revoked, false otherwise.
     */
    public function isRefreshTokenRevoked($tokenId): bool
    {
        $sql  = "SELECT revoked
                 FROM refresh_tokens
                 WHERE id = :id
                 LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $tokenId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            // If no row found, consider it revoked or handle as needed
            return true;
        }

        return (bool)$row['revoked'];
    }

    /**
     * Find a valid refresh token.
     *
     * @param string $refreshToken
     * @return array|null Returns the refresh token record if valid, or null if invalid/expired/revoked.
     */
    public function findValidToken(string $refreshToken): ?array
    {
        $sql = "SELECT rt.id, rt.access_token_id, rt.expires_at, rt.revoked, at.client_id, at.user_id
                FROM refresh_tokens rt
                INNER JOIN access_tokens at ON rt.access_token_id = at.id
                WHERE rt.id = :refresh_token AND rt.expires_at > NOW() AND rt.revoked = 0
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':refresh_token', $refreshToken, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
