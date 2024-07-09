<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240708154300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE key (id INT NOT NULL, uid VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE position (id INT NOT NULL, key_id INT NOT NULL, position VARCHAR(255) NOT NULL, x INT NOT NULL, y INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_462CE4F5D145533 ON position (key_id)');
        $this->addSql('CREATE TABLE puzzle (id INT NOT NULL, key_id INT DEFAULT NULL, width INT NOT NULL, height INT NOT NULL, piece_width INT NOT NULL, piece_height INT NOT NULL, precision INT NOT NULL, pieces_number INT NOT NULL, space_between_pieces INT NOT NULL, puzzle_bar VARCHAR(10) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_22A6DFDFD145533 ON puzzle (key_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON "user" (username)');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5D145533 FOREIGN KEY (key_id) REFERENCES key (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE puzzle ADD CONSTRAINT FK_22A6DFDFD145533 FOREIGN KEY (key_id) REFERENCES key (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE position DROP CONSTRAINT FK_462CE4F5D145533');
        $this->addSql('ALTER TABLE puzzle DROP CONSTRAINT FK_22A6DFDFD145533');
        $this->addSql('DROP TABLE key');
        $this->addSql('DROP TABLE position');
        $this->addSql('DROP TABLE puzzle');
        $this->addSql('DROP TABLE "user"');
    }
}
