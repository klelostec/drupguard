<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210407202311 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD last_analyse_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEF78A6C55 FOREIGN KEY (last_analyse_id) REFERENCES analyse (id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEF78A6C55 ON project (last_analyse_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEF78A6C55');
        $this->addSql('DROP INDEX IDX_2FB3D0EEF78A6C55 ON project');
        $this->addSql('ALTER TABLE project DROP last_analyse_id');
    }
}
