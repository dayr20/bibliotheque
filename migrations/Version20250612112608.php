<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250612112608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_765A9E03B09B680F ON manga
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE manga ADD manga_dex_id VARCHAR(255) DEFAULT NULL, ADD status VARCHAR(255) DEFAULT NULL, ADD tags JSON DEFAULT NULL, ADD year INT DEFAULT NULL, DROP mongo_id, CHANGE cover_image cover_image VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE manga ADD mongo_id VARCHAR(255) NOT NULL, DROP manga_dex_id, DROP status, DROP tags, DROP year, CHANGE cover_image cover_image VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_765A9E03B09B680F ON manga (mongo_id)
        SQL);
    }
}
