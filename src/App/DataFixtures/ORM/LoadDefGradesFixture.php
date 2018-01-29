<?php

namespace App\DataFixtures\ORM;

use CftfBundle\Entity\LsDefGrade;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDefGradesFixture extends AbstractFixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager) {
        $filename = __DIR__.'/../Data/USGrades.csv';

        $fd = fopen($filename, 'r');

        $keys = fgetcsv($fd, 0, ',');

        while (FALSE !== ($rec = fgetcsv($fd, 0, ','))) {
            $level = array_combine($keys, $rec);

            $lsDefGrade = new LsDefGrade();
            $lsDefGrade->setIdentifier($level['UUID']);
            $lsDefGrade->setUri('local:'.$level['UUID']);
            $lsDefGrade->setTitle($level['Title']);
            $lsDefGrade->setDescription($level['Title']);
            $lsDefGrade->setCode($level['Code']);
            $lsDefGrade->setRank($level['Rank']);

            $manager->persist($lsDefGrade);
        }
        fclose($fd);

        $manager->flush();
    }
}
