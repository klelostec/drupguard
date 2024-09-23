<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240923170714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE git_source_settings DROP FOREIGN KEY FK_C4CBDBB7840AB801');
        $this->addSql('DROP INDEX UNIQ_C4CBDBB7840AB801 ON git_source_settings');
        $this->addSql('ALTER TABLE git_source_settings DROP source_plugin_id');
        $this->addSql('ALTER TABLE local_source_settings DROP FOREIGN KEY FK_57064786840AB801');
        $this->addSql('DROP INDEX UNIQ_57064786840AB801 ON local_source_settings');
        $this->addSql('ALTER TABLE local_source_settings DROP source_plugin_id');
        $this->addSql('ALTER TABLE source_plugin ADD local_source_settings_id INT DEFAULT NULL, ADD git_source_settings_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE source_plugin ADD CONSTRAINT FK_5F910272D5A2DEA2 FOREIGN KEY (local_source_settings_id) REFERENCES local_source_settings (id)');
        $this->addSql('ALTER TABLE source_plugin ADD CONSTRAINT FK_5F910272C82A2A8D FOREIGN KEY (git_source_settings_id) REFERENCES git_source_settings (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F910272D5A2DEA2 ON source_plugin (local_source_settings_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F910272C82A2A8D ON source_plugin (git_source_settings_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE source_plugin DROP FOREIGN KEY FK_5F910272D5A2DEA2');
        $this->addSql('ALTER TABLE source_plugin DROP FOREIGN KEY FK_5F910272C82A2A8D');
        $this->addSql('DROP INDEX UNIQ_5F910272D5A2DEA2 ON source_plugin');
        $this->addSql('DROP INDEX UNIQ_5F910272C82A2A8D ON source_plugin');
        $this->addSql('ALTER TABLE source_plugin DROP local_source_settings_id, DROP git_source_settings_id');
        $this->addSql('ALTER TABLE git_source_settings ADD source_plugin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE git_source_settings ADD CONSTRAINT FK_C4CBDBB7840AB801 FOREIGN KEY (source_plugin_id) REFERENCES source_plugin (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4CBDBB7840AB801 ON git_source_settings (source_plugin_id)');
        $this->addSql('ALTER TABLE local_source_settings ADD source_plugin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE local_source_settings ADD CONSTRAINT FK_57064786840AB801 FOREIGN KEY (source_plugin_id) REFERENCES source_plugin (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57064786840AB801 ON local_source_settings (source_plugin_id)');
    }
}
