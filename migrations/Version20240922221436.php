<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240922221436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE source_plugin DROP FOREIGN KEY FK_5F91027265DFF7DE');
        $this->addSql('DROP INDEX UNIQ_5F91027265DFF7DE ON source_plugin');
        $this->addSql('ALTER TABLE source_plugin CHANGE locale_source_settings_id local_source_settings_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE source_plugin ADD CONSTRAINT FK_5F910272D5A2DEA2 FOREIGN KEY (local_source_settings_id) REFERENCES local_source_settings (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F910272D5A2DEA2 ON source_plugin (local_source_settings_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE source_plugin DROP FOREIGN KEY FK_5F910272D5A2DEA2');
        $this->addSql('DROP INDEX UNIQ_5F910272D5A2DEA2 ON source_plugin');
        $this->addSql('ALTER TABLE source_plugin CHANGE local_source_settings_id locale_source_settings_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE source_plugin ADD CONSTRAINT FK_5F91027265DFF7DE FOREIGN KEY (locale_source_settings_id) REFERENCES local_source_settings (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F91027265DFF7DE ON source_plugin (locale_source_settings_id)');
    }
}
