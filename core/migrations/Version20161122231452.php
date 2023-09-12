<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20161122231452 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE salt_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(64) DEFAULT NULL, roles LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', UNIQUE INDEX UNIQ_F9577392F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE salt_user');
    }
}
