<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230310210114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add TOTP secret column';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salt_user ADD totp_secret VARCHAR(255) DEFAULT NULL, ADD totp_enabled TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salt_user DROP totp_secret, DROP totp_enabled');
    }
}
