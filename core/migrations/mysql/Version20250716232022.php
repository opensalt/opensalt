<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250716232022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow an association identifier to be used in multiple packages';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_A84022D4772E836A ON ls_association');
        $this->addSql('DROP INDEX UNIQ_A84022D4841CB121 ON ls_association');
        $this->addSql('CREATE UNIQUE INDEX ls_association_identifier ON ls_association (identifier, ls_doc_identifier)');
        $this->addSql('CREATE UNIQUE INDEX ls_association_uri ON ls_association (uri, ls_doc_identifier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX ls_association_identifier ON ls_association');
        $this->addSql('DROP INDEX ls_association_uri ON ls_association');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A84022D4772E836A ON ls_association (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A84022D4841CB121 ON ls_association (uri)');
    }

}
