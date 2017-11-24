<?php

namespace Application\Migrations;

use CftfBundle\DataFixtures\ORM\LoadDefGradesFixture;
use Doctrine\DBAL\Schema\Schema;
use App\Doctrine\Migration\AbstractFixturesMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160921225507 extends AbstractFixturesMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->loadFixtures(array(
            new LoadDefGradesFixture(),
        ));

        $this->addSql("SELECT 'Loaded Grade Data'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
