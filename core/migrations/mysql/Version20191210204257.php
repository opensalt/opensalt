<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191210204257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subtype and annotation columns to association';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association ADD subtype VARCHAR(255) DEFAULT NULL, ADD annotation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_ls_association ADD subtype VARCHAR(255) DEFAULT NULL, ADD annotation TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_ls_association DROP subtype, DROP annotation');
        $this->addSql('ALTER TABLE ls_association DROP subtype, DROP annotation');
    }
}
