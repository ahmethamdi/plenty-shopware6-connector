<?php declare(strict_types=1);

namespace PlentyConnector\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1702310000CreateTokenSystemTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1702310000;
    }

    public function update(Connection $connection): void
    {
        // Create plenty_package table
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `plenty_package` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `target_amount` DOUBLE NOT NULL,
                `token_reward` INT NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `category_ids` JSON NULL,
                `visibility_type` VARCHAR(50) NOT NULL DEFAULT "all",
                `allowed_customer_ids` JSON NULL,
                `excluded_customer_ids` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Create plenty_package_progress table
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `plenty_package_progress` (
                `id` BINARY(16) NOT NULL,
                `customer_id` BINARY(16) NOT NULL,
                `package_id` BINARY(16) NOT NULL,
                `current_amount` DOUBLE NOT NULL DEFAULT 0,
                `completed_cycles` INT NOT NULL DEFAULT 0,
                `last_reset_at` DATETIME(3) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.plenty_package_progress.customer_id` FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.plenty_package_progress.package_id` FOREIGN KEY (`package_id`)
                    REFERENCES `plenty_package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                UNIQUE KEY `uniq.customer_package` (`customer_id`, `package_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Create plenty_token_transaction table
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `plenty_token_transaction` (
                `id` BINARY(16) NOT NULL,
                `customer_id` BINARY(16) NOT NULL,
                `amount` INT NOT NULL,
                `type` VARCHAR(50) NOT NULL,
                `package_id` BINARY(16) NULL,
                `token_product_id` BINARY(16) NULL,
                `order_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.plenty_token_transaction.customer_id` FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.plenty_token_transaction.package_id` FOREIGN KEY (`package_id`)
                    REFERENCES `plenty_package` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk.plenty_token_transaction.order_id` FOREIGN KEY (`order_id`)
                    REFERENCES `order` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Create plenty_token_product table
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `plenty_token_product` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NULL,
                `token_price` INT NOT NULL,
                `image_url` VARCHAR(500) NULL,
                `stock` INT NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Create plenty_token_order table
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `plenty_token_order` (
                `id` BINARY(16) NOT NULL,
                `customer_id` BINARY(16) NOT NULL,
                `token_product_id` BINARY(16) NOT NULL,
                `token_amount` INT NOT NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT "pending",
                `customer_address` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.plenty_token_order.customer_id` FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.plenty_token_order.token_product_id` FOREIGN KEY (`token_product_id`)
                    REFERENCES `plenty_token_product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Add FK constraint for token_transaction.token_product_id (separate because of circular dependency)
        $connection->executeStatement('
            ALTER TABLE `plenty_token_transaction`
            ADD CONSTRAINT `fk.plenty_token_transaction.token_product_id` FOREIGN KEY (`token_product_id`)
                REFERENCES `plenty_token_product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // Destructive changes can be added here if needed
    }
}
