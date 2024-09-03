<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240831131252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_member (id INT AUTO_INCREMENT NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_member_group (project_member_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_2546355564AB9629 (project_member_id), INDEX IDX_25463555FE54D947 (group_id), PRIMARY KEY(project_member_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_member_user (project_member_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B9D40DEE64AB9629 (project_member_id), INDEX IDX_B9D40DEEA76ED395 (user_id), PRIMARY KEY(project_member_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_member_group ADD CONSTRAINT FK_2546355564AB9629 FOREIGN KEY (project_member_id) REFERENCES project_member (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_member_group ADD CONSTRAINT FK_25463555FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_member_user ADD CONSTRAINT FK_B9D40DEE64AB9629 FOREIGN KEY (project_member_id) REFERENCES project_member (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_member_user ADD CONSTRAINT FK_B9D40DEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_member_group DROP FOREIGN KEY FK_2546355564AB9629');
        $this->addSql('ALTER TABLE project_member_group DROP FOREIGN KEY FK_25463555FE54D947');
        $this->addSql('ALTER TABLE project_member_user DROP FOREIGN KEY FK_B9D40DEE64AB9629');
        $this->addSql('ALTER TABLE project_member_user DROP FOREIGN KEY FK_B9D40DEEA76ED395');
        $this->addSql('DROP TABLE project_member');
        $this->addSql('DROP TABLE project_member_group');
        $this->addSql('DROP TABLE project_member_user');
    }
}
