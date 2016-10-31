<?php

namespace Application\Migrations;

use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Entity\LsDoc;
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
class Version20160928191216 extends AbstractMigration implements ContainerAwareInterface
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
        $qb->select('d')
            ->from('CftfBundle:LsDoc', 'd')
            ->where('d.subject IS NOT NULL OR d.subjectUri IS NOT NULL')
        ;
        /** @var LsDoc[] $docs */
        $docs = $qb->getQuery()->getResult();

        /** @var LsDefSubject[] $subjects */
        $subjects = [];
        foreach ($docs as $doc) {
            if (!($subject = $doc->getSubject())) {
                $subject = ucfirst(preg_replace('#.*/#', '', $doc->getSubjectUri()));
            }

            if (!array_key_exists($subject, $subjects)) {
                $uuid = Uuid::uuid5(Uuid::fromString('cacee394-85b7-11e6-9d43-005056a32dda'), $subject);
                $s = new LsDefSubject();
                $s->setIdentifier($uuid);
                $s->setUri('local:'.$uuid->toString());
                $s->setTitle($subject);
                $s->setHierarchyCode("1");

                $subjects[$subject] = $s;

                $em->persist($s);
            } else {
                $s = $subjects[$subject];
            }

            $doc->addSubject($s);
        }

        $em->flush();

        $this->addSql("SELECT 'Updated subjects'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf(true, 'Cannot revert');
    }
}
