<?php

declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180201165909 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $sql = <<<'xENDx'
INSERT INTO ls_def_licence (identifier, uri, title, licence_text, updated_at)
    SELECT * FROM (SELECT :uuid, :uri, :title, :licence_text, NOW()) AS tmp
    WHERE NOT EXISTS (
            SELECT title FROM ls_def_licence WHERE title = :title
        ) LIMIT 1;
xENDx;
        $insertLicence = $this->connection->prepare($sql);
        $licence_text = 'https://creativecommons.org/licenses/by/4.0/legalcode';
        $uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, $licence_text);
        $params = [
            'uuid' => $uuid->toString(),
            'uri' => 'local:'.$uuid->toString(),
            'title' => 'Attribution 4.0 International',
            'licence_text' => $licence_text,
        ];
        $insertLicence->execute($params);
    }

    public function down(Schema $schema)
    {
        $this->addSql('DELETE FROM ls_def_licence WHERE title = "Attribution 4.0 International" AND licence_text = "https://creativecommons.org/licenses/by/4.0/legalcode"');

    }
}
