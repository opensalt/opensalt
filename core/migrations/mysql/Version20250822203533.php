<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250822203533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Treat each document as a separate graph for items and associations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_A84022D4772E836A ON ls_association');
        $this->addSql('DROP INDEX UNIQ_A84022D4841CB121 ON ls_association');
        $this->addSql('CREATE UNIQUE INDEX ls_association_identifier ON ls_association (identifier, ls_doc_identifier)');
        $this->addSql('CREATE UNIQUE INDEX ls_association_uri ON ls_association (uri, ls_doc_identifier)');
        $this->addSql('DROP INDEX UNIQ_D8D02498772E836A ON ls_item');
        $this->addSql('DROP INDEX UNIQ_D8D02498841CB121 ON ls_item');
        $this->addSql('CREATE UNIQUE INDEX ls_item_identifier ON ls_item (identifier, ls_doc_identifier)');
        $this->addSql('CREATE UNIQUE INDEX ls_item_uri ON ls_item (uri, ls_doc_identifier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX ls_association_identifier ON ls_association');
        $this->addSql('DROP INDEX ls_association_uri ON ls_association');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A84022D4772E836A ON ls_association (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A84022D4841CB121 ON ls_association (uri)');
        $this->addSql('DROP INDEX ls_item_identifier ON ls_item');
        $this->addSql('DROP INDEX ls_item_uri ON ls_item');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8D02498772E836A ON ls_item (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8D02498841CB121 ON ls_item (uri)');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
