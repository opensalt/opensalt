<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250822155740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Go back to an item can only be associated with one framework';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX ls_item_identifier ON ls_item');
        $this->addSql('DROP INDEX ls_item_uri ON ls_item');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8D02498772E836A ON ls_item (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8D02498841CB121 ON ls_item (uri)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D8D02498772E836A ON ls_item');
        $this->addSql('DROP INDEX UNIQ_D8D02498841CB121 ON ls_item');
        $this->addSql('CREATE UNIQUE INDEX ls_item_identifier ON ls_item (identifier, ls_doc_identifier)');
        $this->addSql('CREATE UNIQUE INDEX ls_item_uri ON ls_item (uri, ls_doc_identifier)');
    }

}
