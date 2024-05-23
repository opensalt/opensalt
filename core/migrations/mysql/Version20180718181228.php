<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20180718181228 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX dest_id_idx ON ls_association (destination_node_identifier)');
        $this->addSql('CREATE INDEX orig_id_idx ON ls_association (origin_node_identifier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX dest_id_idx ON ls_association');
        $this->addSql('DROP INDEX orig_id_idx ON ls_association');
    }
}
