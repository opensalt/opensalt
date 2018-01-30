<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180130193130 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX change_time_idx ON salt_change (changed_at)');

        $this->addSql('ALTER TABLE salt_user_doc_acl DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE salt_user_doc_acl ADD id INT AUTO_INCREMENT NOT NULL FIRST, ADD PRIMARY KEY (id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_acl_id ON salt_user_doc_acl (doc_id, user_id)');

        $this->addSql('TRUNCATE audit_salt_user_doc_acl');
        $this->addSql('ALTER TABLE audit_salt_user_doc_acl DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE audit_salt_user_doc_acl ADD id INT NOT NULL FIRST, CHANGE user_id user_id INT DEFAULT NULL, CHANGE doc_id doc_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_salt_user_doc_acl ADD PRIMARY KEY (id, rev)');
        $this->addSql('
INSERT IGNORE INTO audit_salt_user_doc_acl
  (id, user_id, doc_id, rev, access, revtype)
SELECT
  id, user_id, doc_id, (SELECT MIN(ad.rev) FROM audit_ls_doc ad WHERE ad.id = doc_id), access, "INS"
FROM salt_user_doc_acl;
        ');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE audit_salt_user_doc_acl DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE audit_salt_user_doc_acl DROP id, CHANGE user_id user_id INT NOT NULL, CHANGE doc_id doc_id INT NOT NULL');
        $this->addSql('ALTER TABLE audit_salt_user_doc_acl ADD PRIMARY KEY (user_id, doc_id, rev)');

        $this->addSql('ALTER TABLE salt_user_doc_acl MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX uniq_acl_id ON salt_user_doc_acl');
        $this->addSql('ALTER TABLE salt_user_doc_acl DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE salt_user_doc_acl DROP id');
        $this->addSql('ALTER TABLE salt_user_doc_acl ADD PRIMARY KEY (user_id, doc_id)');

        $this->addSql('DROP INDEX change_time_idx ON salt_change');
    }
}
