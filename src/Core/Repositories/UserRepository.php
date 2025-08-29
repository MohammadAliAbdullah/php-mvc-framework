<?php

declare(strict_types=1);

namespace App\Core\Repositories;

use App\Core\Models\User;
use App\Core\Models\UsersAuthScope;
use App\Core\Repositories\Base\BaseRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;
use PDO;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected UsersAuthScope $usersAuthScope;

    public function __construct(PDO $db, UsersAuthScope $usersAuthScope)
    {
        parent::__construct($db, 'user', User::class);

        $this->usersAuthScope = $usersAuthScope;
        $this->usersAuthScope->setDb($db);
    }

    public function findByEmail(string $email): ?User
    {
        $model = $this->model->where('email', '=', $email);

        $users = $model->executeQuery($model->getQuery());
        
        if (!empty($users)) {
            $users = $model->set($users[0]);
            return $users;
        }
        return null;
    }

    public function getUserEntityByUserCredentials(
        string $username,
        string $password,
        string $grantType,
        ClientEntityInterface $clientEntity
    ): ?UserEntityInterface {
        // 1. Look up the user record by username (e.g., email) in your data source
        $user = $this->findByEmail($username);
        if (!$user) {
            // User not found
            return null;
        }

        // 2. Verify the password
        // Assuming $user->getPassword() returns a hashed password
        if (!password_verify($password, $user->getPassword())) {
            // Password mismatch
            return null;
        }

        // 3. Return an instance of UserEntityInterface
        // We'll create an anonymous class implementing the interface
        // and set the identifier to the user's ID.
        return new class($user->user_id) implements UserEntityInterface {
            use EntityTrait;

            public function __construct(private readonly int $id)
            {
                // Use the trait's setIdentifier method
                $this->setIdentifier((string) $id);
            }
        };
    }

    /**
     * Get the scopes for a user by left joining the scopes table.
     *
     * @param int $id
     * @return array<string> List of scope names
     */
    public function getUserScopes(int $id): array
    {
        // $query = "
        //     SELECT us.scopes 
        //     FROM users_auth_scopes us
        //     WHERE us.user_id = :id
        // ";

        // $statement = $this->db->prepare($query);
        // $statement->bindParam(':id', $id, PDO::PARAM_INT);
        // $statement->execute();

        // $result = $statement->fetch(PDO::FETCH_COLUMN);
        
        // return $result ? json_decode($result, true) : [];

        $result = $this->usersAuthScope->select(['scopes'])->where('user_id', '=', $id)->findAll();

        return $result;
    }


    /**
     * Insert or update multiple users
     * 
     * @param array $data Array of user records to insert/update
     * @param array $uniqueKeys The unique keys to check for existing records
     * @return int|false The number of affected rows or false on failure
     */
    public function import(array $data, array $uniqueKeys): int|false
    {
        return $this->model->upsert($data, $uniqueKeys);
    }

    public function customerSearch(string $search): array
    {
        $result = $this->model
                        ->select(['user.user_id as customer_id', 'first_name', 'last_name', 'email', 'CONCAT(user.first_name, " ", user.last_name) as name'])
                        ->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('username', 'like', '%' . $search . '%');

                        $result = $result->findAll();
        return $result;
    }

    public function findUsers(): array
    {
        $users = $this->model->findAll();
        $this->model->clearQuery();

        // Get all user roles with user_id included
        $userRoles = $this->model
                            ->select(['user.user_id', 'role.role_id', 'role.name', 'role.display_name'])
                            ->join('model_has_role', 'model_has_role.model_id', '=', 'user.user_id')
                            ->join('role', 'role.role_id', '=', 'model_has_role.role_id')
                            ->where('model_has_role.model_type', '=', 'user')
                            ->findAll();

        // Group roles by user_id
        $rolesByUserId = [];
        foreach ($userRoles as $role) {
            $userId = $role['user_id'];
            if (!isset($rolesByUserId[$userId])) {
                $rolesByUserId[$userId] = [];
            }
            $rolesByUserId[$userId][] = [
                'role_id' => $role['role_id'],
                'name' => $role['name'],
                'display_name' => $role['display_name']
            ];
        }

        // Add roles to each user
        foreach ($users as &$user) {
            $userId = $user['user_id'];
            $user['userRole'] = $rolesByUserId[$userId] ?? [];
        }

        return $users;
    }

    public function findWithRoles(int $id): ?array
    {
        // Get the specific user
        $user = $this->model->where('user_id', '=', $id)->first();
        if (!$user) {
            return null;
        }

        $this->model->clearQuery();

        // Get user roles for this specific user
        $userRoles = $this->model
                            ->select(['user.user_id', 'role.role_id', 'role.name', 'role.display_name'])
                            ->join('model_has_role', 'model_has_role.model_id', '=', 'user.user_id')
                            ->join('role', 'role.role_id', '=', 'model_has_role.role_id')
                            ->where('model_has_role.model_type', '=', 'user')
                            ->where('user.user_id', '=', $id)
                            ->findAll();

        // Convert user object to array and add roles
        $userData = $user->data;
        $userArray = (array) $userData;
        
        // Add roles to user data
        $userArray['userRole'] = [];
        foreach ($userRoles as $role) {
            $userArray['userRole'][] = [
                'role_id' => $role['role_id'],
                'name' => $role['name'],
                'display_name' => $role['display_name']
            ];
        }

        return $userArray;
    }

    public function getSalesTeamComponentData(array $param = [])
    {
        $query = $this->model
            ->select([
                'user.user_id',
                'user.first_name',
                'user.last_name',
                'user.email',
                'user.image',
                'user.location',
                'user.position',
                'role.name as role_name',
                'role.display_name as role_display_name'
            ])
            ->join('model_has_role', 'model_has_role.model_id', '=', 'user.user_id')
            ->join('role', 'role.role_id', '=', 'model_has_role.role_id')
            ->where('model_has_role.model_type', '=', 'user')
            ->whereIn('role.name', ['sales_executive', 'director', 'project_manager', 'senior_sales_executive'])
            ->where('user.status', '=', 1);

        if (isset($param['item_count']) && $param['item_count'] > 0) {
            $query->limit($param['item_count']);
        }

        $query->orderBy('user.location', 'ASC')
              ->orderBy('user.first_name', 'ASC');

        $results = $query->findAll();

        // Group users by location
        $groupedUsers = [];
        foreach ($results as $user) {
            $location = $user['location'] ?? 'Sydney'; // Default to Sydney if no location
            if (!isset($groupedUsers[$location])) {
                $groupedUsers[$location] = [];
            }

            $imageData = json_decode($user['image'] ?? '{}', true);
            $imageUrl = $imageData['objectURL'] ?? $imageData['url'] ?? '/img/contact/member-' . (count($groupedUsers[$location]) % 8) . '.jpg';

            $groupedUsers[$location][] = [
                'memberImage' => $imageUrl,
                'memberName' => $user['first_name'] . ' ' . $user['last_name'],
                'memberPosition' => $user['position'] ?? $user['role_display_name'] ?? 'Sales Executive'
            ];
        }

        // Format the final structure
        $items = [];
        foreach ($groupedUsers as $location => $teamData) {
            $items[] = [
                'itemName' => $location,
                'teamData' => $teamData
            ];
        }

        // If no results found, return default structure
        if (empty($items)) {
            $items = [
                [
                    "itemName" => "Sydney",
                    "teamData" => [
                        [
                            'memberImage' => '/img/contact/member-0.jpg',
                            'memberName' => 'Devon Lane',
                            'memberPosition' => 'Director'
                        ],
                        [
                            'memberImage' => '/img/contact/member-1.jpg',
                            'memberName' => 'Jane Doe',
                            'memberPosition' => 'Senior Sales Executive'
                        ],
                        [
                            'memberImage' => '/img/contact/member-2.jpg',
                            'memberName' => 'Devon Lane',
                            'memberPosition' => 'Sales Executive'
                        ],
                        [
                            'memberImage' => '/img/contact/member-3.jpeg',
                            'memberName' => 'Jane Doe',
                            'memberPosition' => 'Sales Executive'
                        ]
                    ]
                ],
                [
                    "itemName" => "Melbourne",
                    "teamData" => [
                        [
                            'memberImage' => '/img/contact/member-4.jpg',
                            'memberName' => 'Devon Lane',
                            'memberPosition' => 'Project Manager'
                        ],
                        [
                            'memberImage' => '/img/contact/member-5.jpg',
                            'memberName' => 'Jane Doe',
                            'memberPosition' => 'Project Manager'
                        ],
                        [
                            'memberImage' => '/img/contact/member-6.jpg',
                            'memberName' => 'Devon Lane',
                            'memberPosition' => 'Sales Executive'
                        ],
                        [
                            'memberImage' => '/img/contact/member-7.jpg',
                            'memberName' => 'Jane Doe',
                            'memberPosition' => 'Sales Executive'
                        ]
                    ]
                ]
            ];
        }

        return [
            'sectionTitle' => 'Connect with our sales team',
            'sectionSubtitle' => 'Lorem ipsum dolor sit amet consectetur. Scelerisque urna pellentesque.',
            'items' => $items
        ];
    }
}
