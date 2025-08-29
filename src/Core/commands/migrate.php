<?php

// Load environment variables and the PDO setup
require_once __DIR__ . '/../../../autoload.php';

use Core\commands\MigrateCommand;

try {
    // Create PDO connection
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME'],
            $_ENV['DB_CHARSET']
        ),
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Create and run the migration command
    $migrateCommand = new MigrateCommand($pdo);
    $migrateCommand->execute();

    echo "All migrations completed successfully.\n";

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} 