<?php

// Include autoloader

// Load environment variables and the PDO setup

use App\Core\Database\seeders\ComponentSeeder;

require_once __DIR__ . '/../../../autoload.php';

// Path to the plugins folder
$pluginsPath = __DIR__ . '/../../../plugins';

require_once __DIR__ . '/../../../src/Core/System/utils/functions.php';

// Import the seeder class
$seeder = new ComponentSeeder();
$seeder->seed();

echo "Job seeding completed!\n";
