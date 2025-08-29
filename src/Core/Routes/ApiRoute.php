<?php

namespace App\Core\Routes;


use App\Core\Controllers\Api\PostController;

use App\Core\Routes\Base\Api;
use App\Core\System\Event;

use function App\Core\System\utils\controller;

class ApiRoute extends Api{
    public static array $routes = [
        
        [
            'method' => 'GET',
            'uri' => '/api/posts',
            'controller' => PostController::class,
            'action' => 'index',
        ],
        [
            'method' => 'GET',
            'uri' => '/api/posts/{id}',
            'controller' => PostController::class,
            'action' => 'show',
        ],
        [
            'method' => 'POST',
            'uri' => '/api/posts',
            'controller' => PostController::class,
            'action' => 'create',
        ],
        [
            'method' => 'PUT',
            'uri' => '/api/posts/{id}',
            'controller' => PostController::class,
            'action' => 'update',
        ],
        
    ];
    public static function registerRoutes(){
        Event::on(Route::class, 'add-routes', __CLASS__, function($routes){
            return [array_merge($routes, self::$routes)];
        }, 20);
    }
}
