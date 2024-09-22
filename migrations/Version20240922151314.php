<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240922151314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE source_plugin ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE source_plugin ADD CONSTRAINT FK_5F910272166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('CREATE INDEX IDX_5F910272166D1F9C ON source_plugin (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE source_plugin DROP FOREIGN KEY FK_5F910272166D1F9C');
        $this->addSql('DROP INDEX IDX_5F910272166D1F9C ON source_plugin');
        $this->addSql('ALTER TABLE source_plugin DROP project_id');
    }
}
