<?php

namespace App\Core\Constants;

use App\Core\System\Event;

class Constants {

    /**
     * @var string V_SUBDIR_INSTALL
     * @var bool DEBUG
     * @var int SITE_ID
     * @var string DIR_ROOT
     * @var string DIR_CACHE
     * @var string DIR_THEMES
     * @var string PUBLIC_PATH
     */
    public static array $constants = [
        'APP' => 'default_app',
        'DIR_ROOT' => '/path/to/root',
        'DIR_CACHE' => '/path/to/cache',
        'DIR_THEMES' => '/path/to/themes',
        'DS' => DIRECTORY_SEPARATOR,
        'V_SUBDIR_INSTALL' => '',
        'DEBUG' => false,
        'SITE_ID' => 1,
        'PUBLIC_PATH' => '/public/'
    ];

    public function __construct()
    {
        $constants = Event::trigger(self::class, 'add-constants', self::$constants);
        if(count($constants) > 0){
            self::$constants = $constants;
        }
        foreach($constants as $constant => $value){
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    public static function addConstant(string $constantName, string $constantValue): void
    {
        if(!isset(self::$constants[$constantName])){
            self::$constants[$constantName] = $constantValue;
        }
    }
}
