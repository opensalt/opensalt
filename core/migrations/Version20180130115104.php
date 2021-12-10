<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20180130115104 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_salt_user ADD status INT DEFAULT 2 NOT NULL');
        $this->addSql('UPDATE audit_salt_user SET status = locked');
        $this->addSql('ALTER TABLE audit_salt_user DROP locked');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_salt_user ADD locked TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('UPDATE audit_salt_user SET locked = status');
        $this->addSql('ALTER TABLE audit_salt_user DROP status');
    }
}
