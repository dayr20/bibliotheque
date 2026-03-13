<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reading_list and reading_progress tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reading_list (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            manga_id INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CC844E6A76ED395 (user_id),
            INDEX IDX_CC844E67B6461 (manga_id),
            UNIQUE INDEX UNIQ_USER_MANGA (user_id, manga_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE reading_progress (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            manga_id INT NOT NULL,
            last_chapter_id INT NOT NULL,
            last_read_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_4E7E3F6A76ED395 (user_id),
            INDEX IDX_4E7E3F67B6461 (manga_id),
            INDEX IDX_4E7E3F6B5FC0459 (last_chapter_id),
            UNIQUE INDEX UNIQ_PROGRESS_USER_MANGA (user_id, manga_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE reading_list ADD CONSTRAINT FK_RL_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reading_list ADD CONSTRAINT FK_RL_MANGA FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reading_progress ADD CONSTRAINT FK_RP_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reading_progress ADD CONSTRAINT FK_RP_MANGA FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reading_progress ADD CONSTRAINT FK_RP_CHAPTER FOREIGN KEY (last_chapter_id) REFERENCES chapter (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reading_progress');
        $this->addSql('DROP TABLE reading_list');
    }
}
