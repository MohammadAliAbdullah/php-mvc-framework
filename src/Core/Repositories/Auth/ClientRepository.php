<?php

declare(strict_types=1);

namespace App\Core\Repositories\Auth;


use App\Core\Models\Client;
use App\Core\Repositories\Base\BaseRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use PDO;

/**
 * ClientRepository loads client data from the database to handle
 * client validation in league/oauth2-server's flows.
 */
class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{

    /**
     * Inject a PDO instance via the constructor.
     */
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'clients', Client::class);
    }

    /**
     * {@inheritdoc}
     * Load the client by its identifier from the database and return a ClientEntityInterface.
     */
    public function getClientEntity($clientIdentifier): ?ClientEntityInterface
    {
        $sql = "SELECT id, name, redirect_uri, is_confidential
                FROM clients
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $clientIdentifier);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        // Return an instance of a ClientEntityInterface (anonymous class)
        return new class(
            $row['id'],
            $row['name'],
            $row['redirect_uri'],
            $row['scopes'],
            (bool)$row['is_confidential']
        ) implements ClientEntityInterface {
            use EntityTrait;

            public function __construct(
                protected string $identifier,
                private string   $name,
                private string   $scopes,
                private string   $redirectUri,
                private bool     $isConfidential
            ) {
                $this->setIdentifier($identifier);
            }

            public function getName(): string
            {
                return $this->name;
            }
            public function getScopes(): string
            {
                return $this->scopes;
            }

            /**
             * Redirect URI can be a string or array. If your DB stores
             * multiple URIs (JSON, comma-separated, etc.), adjust accordingly.
             */
            public function getRedirectUri(): array|string
            {
                return $this->redirectUri;
            }

            public function isConfidential(): bool
            {
                return $this->isConfidential;
            }
        };
    }

    /**
     * {@inheritdoc}
     * Validate the client's secret and optional grant type.
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        $sql = "SELECT secret, is_confidential
                FROM clients
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $clientIdentifier);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        // If the client is confidential, check the secret.
        // If not confidential, the secret might be ignored (public client).
        $isConfidential = (bool)$row['is_confidential'];
        if ($isConfidential) {
            if (!$clientSecret || $row['secret'] !== $clientSecret) {
                return false;
            }
        }

        // Optionally check $grantType if you want to restrict certain grants
        // e.g.: if ($grantType === 'client_credentials' && !$isConfidential) { return false; }

        return true;
    }
    /**
     * Validate a token for a client.
     *
     * @param string $token The token to validate.
     * @return array|null Returns an array with token details if valid, null otherwise.
     */
    public function validateToken(string $token): ?array
    {
        $sql = "SELECT at.token, at.client_id, at.user_id, at.expires_at, c.name AS client_name
                FROM access_tokens at
                INNER JOIN clients c ON at.client_id = c.id
                WHERE at.token = :token AND at.expires_at > NOW()
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'token' => $row['token'],
            'client_id' => $row['client_id'],
            'user_id' => $row['user_id'],
            'expires_at' => $row['expires_at'],
            'client_name' => $row['client_name'],
        ];
    }

    /**
     * Create a new client and return the Client object.
     *
     * @param array $data
     * @return Client
     */
    public function createClient(array $data): Client
    {
        $sql = "INSERT INTO clients (name, secret, scopes, created_at, revoked)
                VALUES (:name, :secret, :scopes, :created_at, 0)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':secret', $data['secret'], PDO::PARAM_STR);
        $stmt->bindValue(':scopes', json_encode($data['scopes']), PDO::PARAM_STR);
        $stmt->bindValue(':created_at', $data['created_at'], PDO::PARAM_STR);

        $stmt->execute();

        $clientId = (int)$this->db->lastInsertId();
        $data['id'] = $clientId;

        $client = new Client();
        $client->set($data);
        return $client;
    }
    /**
     * Revoke a client by ID.
     *
     * @param int $clientId
     * @return bool
     */
    public function revokeClient(int $clientId): bool
    {
        $sql = "UPDATE clients SET revoked = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        return true;
    }
}
