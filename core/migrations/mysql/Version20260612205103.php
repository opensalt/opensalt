<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612205103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index for ls_doc_identifier';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX ls_doc_identifier_idx ON ls_association (ls_doc_identifier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX ls_doc_identifier_idx ON ls_association');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
