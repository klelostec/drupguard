<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240917172719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_member_user DROP FOREIGN KEY FK_B9D40DEEA76ED395');
        $this->addSql('ALTER TABLE project_member_user DROP FOREIGN KEY FK_B9D40DEE64AB9629');
        $this->addSql('ALTER TABLE project_member_group DROP FOREIGN KEY FK_2546355564AB9629');
        $this->addSql('ALTER TABLE project_member_group DROP FOREIGN KEY FK_25463555FE54D947');
        $this->addSql('DROP TABLE project_member_user');
        $this->addSql('DROP TABLE project_member_group');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_MACHINENAME ON project (machine_name)');
        $this->addSql('ALTER TABLE project_member ADD project_id INT NOT NULL, ADD user_id INT DEFAULT NULL, ADD groups_id INT DEFAULT NULL, ADD role VARCHAR(255) NOT NULL, DROP roles');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132F373DCF FOREIGN KEY (groups_id) REFERENCES `group` (id)');
        $this->addSql('CREATE INDEX IDX_67401132166D1F9C ON project_member (project_id)');
        $this->addSql('CREATE INDEX IDX_67401132A76ED395 ON project_member (user_id)');
        $this->addSql('CREATE INDEX IDX_67401132F373DCF ON project_member (groups_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_member_user (project_member_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B9D40DEE64AB9629 (project_member_id), INDEX IDX_B9D40DEEA76ED395 (user_id), PRIMARY KEY(project_member_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE project_member_group (project_member_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_2546355564AB9629 (project_member_id), INDEX IDX_25463555FE54D947 (group_id), PRIMARY KEY(project_member_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_member_user ADD CONSTRAINT FK_B9D40DEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_member_user ADD CONSTRAINT FK_B9D40DEE64AB9629 FOREIGN KEY (project_member_id) REFERENCES project_member (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_member_group ADD CONSTRAINT FK_2546355564AB9629 FOREIGN KEY (project_member_id) REFERENCES project_member (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_member_group ADD CONSTRAINT FK_25463555FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_MACHINENAME ON project');
        $this->addSql('ALTER TABLE project_member DROP FOREIGN KEY FK_67401132166D1F9C');
        $this->addSql('ALTER TABLE project_member DROP FOREIGN KEY FK_67401132A76ED395');
        $this->addSql('ALTER TABLE project_member DROP FOREIGN KEY FK_67401132F373DCF');
        $this->addSql('DROP INDEX IDX_67401132166D1F9C ON project_member');
        $this->addSql('DROP INDEX IDX_67401132A76ED395 ON project_member');
        $this->addSql('DROP INDEX IDX_67401132F373DCF ON project_member');
        $this->addSql('ALTER TABLE project_member ADD roles JSON NOT NULL COMMENT \'(DC2Type:json)\', DROP project_id, DROP user_id, DROP groups_id, DROP role');
    }
}
