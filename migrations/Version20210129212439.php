<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210129212439 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create user table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE user (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL --(DC2Type:datetimetz_immutable)
        , name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , PRIMARY KEY(id))');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE user');
    }
}
