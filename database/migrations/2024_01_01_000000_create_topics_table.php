<?php

declare(strict_types=1);

use PDO;
use PDOException;

return new class
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS topics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(190) NOT NULL UNIQUE,
                title VARCHAR(140) NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                is_locked TINYINT(1) NOT NULL DEFAULT 0,
                is_pinned TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT topics_user_fk FOREIGN KEY (user_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->createIndex($pdo, 'CREATE INDEX topics_user_id_index ON topics (user_id)');
        $this->createIndex($pdo, 'CREATE INDEX topics_pinned_created_index ON topics (is_pinned DESC, created_at DESC)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS topics');
    }

    private function createIndex(PDO $pdo, string $sql): void
    {
        try {
            $pdo->exec($sql);
        } catch (PDOException $exception) {
            if ($exception->errorInfo[1] !== 1061) {
                throw $exception;
            }
        }
    }
};
