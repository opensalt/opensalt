<?php

namespace Application\Migrations;

use CftfBundle\Entity\LsDefItemType;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160927144905 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = NULL) {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->select('DISTINCT i.type')
            ->from('CftfBundle:LsItem', 'i', 'i.type')
            ->where('i.type IS NOT NULL')
            ;
        $res = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        $types = array_keys($res);

        $qb = $em->createQueryBuilder();
        $qb->select('DISTINCT t.title')
            ->from('CftfBundle:LsDefItemType', 't', 't.title')
            ;
        $itemTypes = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        foreach ($types as $type) {
            if (array_key_exists($type, $itemTypes)) {
                continue;
            }
            $uuid = Uuid::uuid5(Uuid::fromString('cba3b522-84c4-11e6-a5a9-005056a32dda'), $type);
            $t = new LsDefItemType();
            $t->setIdentifier($uuid);
            $t->setUri('local:'.$uuid->toString());
            $t->setTitle($type);
            $t->setCode($type);
            $t->setHierarchyCode('1');

            $em->persist($t);
        }

        $em->flush();

        $this->addSql('
UPDATE ls_item i
   SET i.item_type_id = (
     SELECT t.id
       FROM ls_def_item_type t
      WHERE t.title = i.type
   )
 WHERE i.type IS NOT NULL
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('
UPDATE ls_item i
   SET i.type = (
     SELECT t.title
       FROM ls_def_item_type t
      WHERE t.id = i.item_type_id
   ), i.item_type_id = NULL
        ');

        $this->addSql('
TRUNCATE ls_def_item_type
        ');
    }
}
