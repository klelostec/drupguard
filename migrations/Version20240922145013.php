<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240922145013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE git_source_settings (id INT AUTO_INCREMENT NOT NULL, repository VARCHAR(255) NOT NULL, branch VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE local_source_settings (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source_plugin (id INT AUTO_INCREMENT NOT NULL, locale_source_settings_id INT DEFAULT NULL, git_source_settings_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_5F91027265DFF7DE (locale_source_settings_id), UNIQUE INDEX UNIQ_5F910272C82A2A8D (git_source_settings_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE source_plugin ADD CONSTRAINT FK_5F91027265DFF7DE FOREIGN KEY (locale_source_settings_id) REFERENCES local_source_settings (id)');
        $this->addSql('ALTER TABLE source_plugin ADD CONSTRAINT FK_5F910272C82A2A8D FOREIGN KEY (git_source_settings_id) REFERENCES git_source_settings (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE source_plugin DROP FOREIGN KEY FK_5F91027265DFF7DE');
        $this->addSql('ALTER TABLE source_plugin DROP FOREIGN KEY FK_5F910272C82A2A8D');
        $this->addSql('DROP TABLE git_source_settings');
        $this->addSql('DROP TABLE local_source_settings');
        $this->addSql('DROP TABLE source_plugin');
    }
}
