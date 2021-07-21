<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210721090957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assignee_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , title VARCHAR(255) NOT NULL, description CLOB NOT NULL, due_date DATETIME DEFAULT NULL --(DC2Type:datetimetz_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetimetz_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetimetz_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_527EDB2559EC7D60 ON task (assignee_id)');
        $this->addSql('CREATE TABLE user (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL --(DC2Type:datetimetz_immutable)
        , name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, salt VARCHAR(100) NOT NULL, encoder VARCHAR(255) NOT NULL, password_expires_at DATETIME DEFAULT NULL --(DC2Type:datetimetz_immutable)
        , PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE user');
    }
}
