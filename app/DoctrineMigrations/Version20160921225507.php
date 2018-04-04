<?php

namespace Application\Migrations;

use App\DataFixtures\ORM\LoadDefGradesFixture;
use Doctrine\DBAL\Schema\Schema;
use App\Doctrine\Migration\AbstractFixturesMigration;

class Version20160921225507 extends AbstractFixturesMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Add new db tables used for auditing so that the fixture can be loaded
        $this->connection->exec('CREATE TABLE IF NOT EXISTS audit_revision (id INT AUTO_INCREMENT NOT NULL, timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', username VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->connection->exec('CREATE TABLE IF NOT EXISTS audit_ls_def_grade (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, rank INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_45e38822f0d366b3b685da14d9e5debb_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->loadFixtures(array(
            new LoadDefGradesFixture(),
        ));

        // remove db tables used for auditing, they will be added again later
        $this->connection->exec('DROP TABLE IF EXISTS audit_ls_def_grade');
        $this->connection->exec('DROP TABLE IF EXISTS audit_revision');

        $this->addSql("SELECT 'Loaded Grade Data'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
