<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250725195429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename salt_org to salt_group as they are now access groups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE salt_org TO salt_group');
        $this->addSql('ALTER TABLE salt_group RENAME INDEX uniq_762fb035e237e06 TO UNIQ_7CB58B3C5E237E06');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salt_group RENAME INDEX uniq_7cb58b3c5e237e06 TO UNIQ_762FB035E237E06');
        $this->addSql('RENAME TABLE salt_group TO salt_org');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
