<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191213225240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make subtype names unique';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B5759A895E237E06 ON salt_association_subtype (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_B5759A895E237E06 ON salt_association_subtype');
    }
}
