<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250502202235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adjust ls_def_concept to require title';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_concept CHANGE title title VARCHAR(1024) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_concept CHANGE title title VARCHAR(1024) DEFAULT NULL
        SQL);
    }

}
