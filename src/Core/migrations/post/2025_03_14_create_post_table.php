<?php

declare(strict_types=1);

class CreatePostTable
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run the migration to create the post table.
     */
    public function up(): void
    {
        $query = "
            CREATE TABLE IF NOT EXISTS `post` (
                `post_id` INT unsigned NOT NULL AUTO_INCREMENT,
                `admin_id` INT unsigned NOT NULL DEFAULT '0',
                `site_id` INT unsigned NOT NULL DEFAULT '0',
                `status` varchar(20) NOT NULL DEFAULT 'publish',
                `image` json NULL DEFAULT NULL,
                `comment_status` varchar(20) NOT NULL DEFAULT 'open',
                `password` varchar(191) NOT NULL DEFAULT '',
                `parent` INT unsigned NOT NULL DEFAULT '0',
                `sort_order` INT UNSIGNED NOT NULL DEFAULT '0',
                `type` varchar(20) NOT NULL DEFAULT 'post',
                `template` varchar(191) NOT NULL DEFAULT '',
                `comment_count` INT NOT NULL DEFAULT '0',
                `views` INT unsigned NOT NULL DEFAULT '0',
                `description` text NULL DEFAULT NULL,
                `description_one` text NULL DEFAULT NULL,
                `description_two` text NULL DEFAULT NULL,
                `description_three` text NULL DEFAULT NULL,

                keyline_quote varchar(191) NULL,
                feature_image_thumb json NULL,
                feature_image json NULL,
                image_banner json NULL,
                image_thumb json NULL,
                main_image_one json NULL,
                main_image_two json NULL,
                is_featured tinyint(1) NULL,
                title varchar(500) NOT NULL,

                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`post_id`),
                KEY `type_status_date` (`type`,`status`,`sort_order`,`created_at`,`post_id`),
                KEY `parent` (`parent`),
                KEY `author` (`admin_id`),
                KEY `site_id` (`site_id`),
                KEY `updated_at` (`updated_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        try {
            $this->pdo->exec($query);
            echo "Table 'post' created successfully.\n";
        } catch (PDOException $e) {
            echo "Error creating table 'post': " . $e->getMessage() . "\n";
        }
    }

    /**
     * Rollback the migration by dropping the post table.
     */
    public function down(): void
    {
        $query = "DROP TABLE IF EXISTS `post`;";

        try {
            $this->pdo->exec($query);
            echo "Table 'post' dropped successfully.\n";
        } catch (PDOException $e) {
            echo "Error dropping table 'post': " . $e->getMessage() . "\n";
        }
    }
} 