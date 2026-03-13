<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add review table for manga comments and ratings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE review (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            manga_id INT NOT NULL,
            rating SMALLINT NOT NULL,
            comment LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_REVIEW_USER (user_id),
            INDEX IDX_REVIEW_MANGA (manga_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_REVIEW_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_REVIEW_MANGA FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE review');
    }
}
