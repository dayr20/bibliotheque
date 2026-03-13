<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313300000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification table, avatar and display_name to user';
    }

    public function up(Schema $schema): void
    {
        // Avatar + display name on user
        $this->addSql('ALTER TABLE user ADD avatar_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD display_name VARCHAR(100) DEFAULT NULL');

        // Notification table
        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_NOTIF_USER (user_id),
            INDEX IDX_NOTIF_READ (user_id, is_read),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_NOTIF_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE user DROP avatar_filename');
        $this->addSql('ALTER TABLE user DROP display_name');
    }
}
