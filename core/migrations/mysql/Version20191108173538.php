<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191108173538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mirroring tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mirror_oauth (id INT AUTO_INCREMENT NOT NULL, endpoint VARCHAR(255) NOT NULL, auth_key VARCHAR(255) NOT NULL, auth_secret VARCHAR(255) NOT NULL, updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mirror_framework (id INT AUTO_INCREMENT NOT NULL, server_id INT DEFAULT NULL, url VARCHAR(255) NOT NULL, identifier VARCHAR(255) NOT NULL, creator VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, include TINYINT(1) NOT NULL, priority INT DEFAULT 0 NOT NULL, status VARCHAR(255) DEFAULT \'new\' NOT NULL, status_count INT DEFAULT 0 NOT NULL, last_check DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', last_success DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', last_failure DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', last_change DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', next_check DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', error_type VARCHAR(255) DEFAULT NULL, updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', last_content LONGBLOB DEFAULT NULL, last_success_content LONGBLOB DEFAULT NULL, INDEX IDX_F6BDD7191844E6B7 (server_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mirror_server (id INT AUTO_INCREMENT NOT NULL, credentials_id INT DEFAULT NULL, url VARCHAR(255) NOT NULL, api_type VARCHAR(255) NOT NULL, check_server TINYINT(1) NOT NULL, add_found TINYINT(1) NOT NULL, priority INT DEFAULT 0 NOT NULL, next_check DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', last_check DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_F43F213F41E8B2E5 (credentials_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mirror_log (id INT AUTO_INCREMENT NOT NULL, mirror_id INT NOT NULL, status VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, occurred_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_3B7127A5FAC830AC (mirror_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mirror_framework ADD CONSTRAINT FK_F6BDD7191844E6B7 FOREIGN KEY (server_id) REFERENCES mirror_server (id)');
        $this->addSql('ALTER TABLE mirror_server ADD CONSTRAINT FK_F43F213F41E8B2E5 FOREIGN KEY (credentials_id) REFERENCES mirror_oauth (id)');
        $this->addSql('ALTER TABLE mirror_log ADD CONSTRAINT FK_3B7127A5FAC830AC FOREIGN KEY (mirror_id) REFERENCES mirror_framework (id)');
        $this->addSql('ALTER TABLE ls_doc ADD mirrored_framework_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_doc ADD CONSTRAINT FK_9AE8CF1FF66A84B6 FOREIGN KEY (mirrored_framework_id) REFERENCES mirror_framework (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9AE8CF1FF66A84B6 ON ls_doc (mirrored_framework_id)');
        $this->addSql('ALTER TABLE audit_ls_doc ADD mirrored_framework_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror_server DROP FOREIGN KEY FK_F43F213F41E8B2E5');
        $this->addSql('ALTER TABLE ls_doc DROP FOREIGN KEY FK_9AE8CF1FF66A84B6');
        $this->addSql('ALTER TABLE mirror_log DROP FOREIGN KEY FK_3B7127A5FAC830AC');
        $this->addSql('ALTER TABLE mirror_framework DROP FOREIGN KEY FK_F6BDD7191844E6B7');
        $this->addSql('DROP TABLE mirror_oauth');
        $this->addSql('DROP TABLE mirror_framework');
        $this->addSql('DROP TABLE mirror_server');
        $this->addSql('DROP TABLE mirror_log');
        $this->addSql('DROP INDEX UNIQ_9AE8CF1FF66A84B6 ON ls_doc');
        $this->addSql('ALTER TABLE audit_ls_doc DROP mirrored_framework_id');
        $this->addSql('ALTER TABLE ls_doc DROP mirrored_framework_id');
    }
}
