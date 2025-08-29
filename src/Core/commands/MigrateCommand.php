<?php

namespace Core\commands;

use PDO;
use PDOException;
use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class MigrateCommand {
    private PDO $db;
    private string $migrationsPath;
    private array $executedMigrations = [];

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->migrationsPath = __DIR__ . '/../migrations';
        $this->createMigrationsTable();
        $this->loadExecutedMigrations();
    }

    private function createMigrationsTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($sql);
    }

    private function loadExecutedMigrations(): void {
        $stmt = $this->db->query("SELECT migration FROM migrations");
        $this->executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getMigrationFiles(bool $foreignKeys = false): array {
        $migrations = [];
        
        $directory = new RecursiveDirectoryIterator($this->migrationsPath);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($regex as $file) {
            $filePath = $file[0];
            $relativePath = str_replace($this->migrationsPath . '/', '', $filePath);
            
            // Skip if already executed
            if (in_array($relativePath, $this->executedMigrations)) {
                continue;
            }

            $isForeignKey = str_starts_with($relativePath, 'z-foreign-keys/');
            
            // Only include foreign key migrations if specifically requested
            if ($foreignKeys && $isForeignKey) {
                $migrations[] = $filePath;
            } elseif (!$foreignKeys && !$isForeignKey) {
                $migrations[] = $filePath;
            }
        }

        sort($migrations);
        return $migrations;
    }

    private function executeMigration(string $file): void {
        $relativePath = str_replace($this->migrationsPath . '/', '', $file);
        
        try {
            // Include the migration file
            require_once $file;
            
            // Get all declared classes before and after including the file
            $declaredClasses = get_declared_classes();
            $migrationClass = end($declaredClasses);
            
            // Create an instance of the migration class and execute it
            if (class_exists($migrationClass)) {
                $migration = new $migrationClass($this->db);
                $migration->up();
                
                // Record the migration
                $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
                $stmt->execute([$relativePath]);
                
                echo "Executed migration: $relativePath\n";
            } else {
                throw new \Exception("Migration class not found in file: $file");
            }
        } catch (\Exception $e) {
            echo "Error executing migration $relativePath: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function hasUnexecutedNonForeignKeyMigrations(): bool {
        $directory = new RecursiveDirectoryIterator($this->migrationsPath);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($regex as $file) {
            $relativePath = str_replace($this->migrationsPath . '/', '', $file[0]);
            
            if (!str_starts_with($relativePath, 'z-foreign-keys/') && 
                !in_array($relativePath, $this->executedMigrations)) {
                return true;
            }
        }

        return false;
    }

    private function isInTransaction(): bool {
        try {
            return $this->db->inTransaction();
        } catch (PDOException $e) {
            return false;
        }
    }

    private function safeRollback(): void {
        if ($this->isInTransaction()) {
            try {
                $this->db->rollBack();
            } catch (PDOException $e) {
                // Log the rollback error but don't throw it
                echo "Warning: Rollback failed: " . $e->getMessage() . "\n";
            }
        }
    }

    private function safeCommit(): void {
        if ($this->isInTransaction()) {
            try {
                $this->db->commit();
            } catch (PDOException $e) {
                echo "Warning: Commit failed: " . $e->getMessage() . "\n";
                $this->safeRollback();
                throw $e;
            }
        }
    }

    private function executeMigrationBatch(array $files, string $type = 'regular'): void {
        if (empty($files)) {
            echo "No new {$type} migrations to execute.\n";
            return;
        }

        // Start transaction if not already in one
        if (!$this->isInTransaction()) {
            $this->db->beginTransaction();
        }

        try {
            foreach ($files as $file) {
                $this->executeMigration($file);
            }
            $this->safeCommit();
            echo "{$type} migrations completed successfully.\n";
        } catch (\Exception $e) {
            $this->safeRollback();
            echo "{$type} migrations failed. Rolling back changes.\n";
            throw $e;
        }
    }

    public function execute(): void {
        try {
            // First, execute regular migrations
            $this->executeMigrationBatch($this->getMigrationFiles(false), 'regular');

            // Reload executed migrations before checking for foreign keys
            $this->loadExecutedMigrations();

            // Check if all non-foreign-key migrations are done
            if ($this->hasUnexecutedNonForeignKeyMigrations()) {
                echo "Skipping foreign key migrations - some regular migrations are still pending.\n";
                return;
            }

            // Now handle foreign key migrations
            // $this->executeMigrationBatch($this->getMigrationFiles(true), 'foreign key');

        } catch (\Exception $e) {
            // Ensure any remaining transaction is rolled back
            $this->safeRollback();
            throw $e;
        }
    }
} 