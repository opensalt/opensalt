<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180219235129 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
CREATE INDEX changed_doc ON audit_salt_change (doc_id, changed_at);
        ');
    }

    public function down(Schema $schema)
    {
        $this->addSql('
DROP INDEX changed_doc ON audit_salt_change;
        ');
    }
}
