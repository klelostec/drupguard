<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240923155537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE git_source_settings (id INT AUTO_INCREMENT NOT NULL, source_plugin_id INT DEFAULT NULL, repository VARCHAR(255) NOT NULL, branch VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C4CBDBB7840AB801 (source_plugin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE local_source_settings (id INT AUTO_INCREMENT NOT NULL, source_plugin_id INT DEFAULT NULL, path VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_57064786840AB801 (source_plugin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source_plugin (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_5F910272166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE git_source_settings ADD CONSTRAINT FK_C4CBDBB7840AB801 FOREIGN KEY (source_plugin_id) REFERENCES source_plugin (id)');
        $this->addSql('ALTER TABLE local_source_settings ADD CONSTRAINT FK_57064786840AB801 FOREIGN KEY (source_plugin_id) REFERENCES source_plugin (id)');
        $this->addSql('ALTER TABLE source_plugin ADD CONSTRAINT FK_5F910272166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE git_source_settings DROP FOREIGN KEY FK_C4CBDBB7840AB801');
        $this->addSql('ALTER TABLE local_source_settings DROP FOREIGN KEY FK_57064786840AB801');
        $this->addSql('ALTER TABLE source_plugin DROP FOREIGN KEY FK_5F910272166D1F9C');
        $this->addSql('DROP TABLE git_source_settings');
        $this->addSql('DROP TABLE local_source_settings');
        $this->addSql('DROP TABLE source_plugin');
    }
}
