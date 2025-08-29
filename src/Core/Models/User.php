<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Models\Base\Model;
use function App\Core\System\utils\session;

class User extends Model
{
    public int $user_id;
    public int $user_group_id;
    public int $site_id;
    public string $username;
    public string $first_name;
    public string $last_name;
    public string $password;
    public string $email;
    public string $phone_number;
    public string $url;
    public int $status;
    public string $display_name;
    public string $avatar;
    public ?string $bio;
    public string $token;
    public int $subscribe;
    public string $created_at;
    public string $updated_at;

    private static $namespace = 'user';
    
    
    public function __construct() 
    {
        parent::__construct();
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @ Currently logged in user data or empty array if guest
     * @return mixed
     */
    public static function current() {
        return session(self::$namespace, []);
    }
}
