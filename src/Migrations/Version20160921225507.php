<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160921225507 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $filename = __DIR__.'/../../src/DataFixtures/Data/USGrades.csv';

        $fd = fopen($filename, 'rb');

        $keys = fgetcsv($fd, 0, ',');

        while (FALSE !== ($rec = fgetcsv($fd, 0, ','))) {
            $level = array_combine($keys, $rec);

            $this->addSql('
INSERT IGNORE INTO ls_def_grade
(identifier, uri, updated_at, title, description, code, rank)
VALUES
(:UUID, CONCAT("level:", :UUID), NOW(), :Title, :Title, :Code, :Rank);
            ', $level);
        }
        fclose($fd);

        $this->addSql("SELECT 'Loaded Grade Data'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
