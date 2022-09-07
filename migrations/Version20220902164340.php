<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220902164340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyse DROP FOREIGN KEY FK_351B0C7E166D1F9C');
        $this->addSql('ALTER TABLE analyse ADD CONSTRAINT FK_351B0C7E166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE analyse_item DROP FOREIGN KEY FK_FC483A341EFE06BF');
        $this->addSql('ALTER TABLE analyse_item ADD CONSTRAINT FK_FC483A341EFE06BF FOREIGN KEY (analyse_id) REFERENCES analyse (id)');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEF78A6C55');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEF78A6C55 FOREIGN KEY (last_analyse_id) REFERENCES analyse (id)');
        $this->addSql('ALTER TABLE user ADD token_api LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyse DROP FOREIGN KEY FK_351B0C7E166D1F9C');
        $this->addSql('ALTER TABLE analyse ADD CONSTRAINT FK_351B0C7E166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE analyse_item DROP FOREIGN KEY FK_FC483A341EFE06BF');
        $this->addSql('ALTER TABLE analyse_item ADD CONSTRAINT FK_FC483A341EFE06BF FOREIGN KEY (analyse_id) REFERENCES analyse (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEF78A6C55');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEF78A6C55 FOREIGN KEY (last_analyse_id) REFERENCES analyse (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user` DROP token_api');
    }
}
