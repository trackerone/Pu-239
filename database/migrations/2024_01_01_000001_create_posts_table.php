<?php

declare(strict_types=1);

use PDO;
use PDOException;

return new class
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS posts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                body_md LONGTEXT NOT NULL,
                body_html LONGTEXT NOT NULL,
                edited_at TIMESTAMP NULL DEFAULT NULL,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT posts_topic_fk FOREIGN KEY (topic_id) REFERENCES topics(id),
                CONSTRAINT posts_user_fk FOREIGN KEY (user_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->createIndex($pdo, 'CREATE INDEX posts_topic_id_index ON posts (topic_id)');
        $this->createIndex($pdo, 'CREATE INDEX posts_user_id_index ON posts (user_id)');
        $this->createIndex($pdo, 'CREATE INDEX posts_created_at_index ON posts (created_at)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS posts');
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
