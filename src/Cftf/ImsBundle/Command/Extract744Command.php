<?php

namespace Cftf\ImsBundle\Command;

use Buzz\Browser;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class Extract744Command extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('extract:tx-744')
            ->setDescription('Extract the 74.4 section')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $browser = new Browser();
        $page = $browser->get('http://ritter.tea.state.tx.us/rules/tac/chapter074/ch074a.html');

        $crawler = new Crawler($page->getContent());
        $crawler = $crawler->filter('body > p');

        $lines = $crawler->extract(['class', '_text']);
        $elpLines = [];
        $inElp = false;
        foreach ($lines as [$class, $text]) {
            if ('SECTIONHEADING' === $class) {
                if (preg_match('/74\.4/', $text)) {
                    $inElp = true;
                } else {
                    $inElp = false;
                }
            }

            if ($inElp) {
                $text = preg_replace('/\s*\r\n\s*/', ' ', $text);
                $elpLines[] = [$class, $text];
            }
        }

        // SECTIONHEADING
        //   SUBSECTIONa
        //     PARAGRAPH1
        //       SUBPARAGRAPHA
        //         CLAUSEi
        //           SUBCLAUSEI
        //   SOURCENOTE
        $levels = [
            'SECTIONHEADING' => 0,
            'SUBSECTIONa' => 1,
            'PARAGRAPH1' => 2,
            'SUBPARAGRAPHA' => 3,
            'CLAUSEi' => 4,
            'SUBCLAUSEI' => 5,
            'SOURCENOTE' => 6,
        ];

        [$class, $text] = array_shift($elpLines);
        $lsDoc = new LsDoc();
        $lsDoc->setTitle($text);
        $lsDoc->setCreator('TEKS-STAAR Core Learning Standards');
        $em->persist($lsDoc);

        $lastLevel = 0;
        $parents = [$lsDoc];

        foreach ($elpLines as [$class, $text]) {
            if ('SOURCENOTE' === $class) {
                continue;
            }

            if (preg_match('/^([(][^)]*[)])\W+(.*)/', $text, $matches)) {
                $lsItem = new LsItem();
                $lsItem->setLsDoc($lsDoc);
                $lsItem->setHumanCodingScheme($matches[1]);
                $lsItem->setFullStatement($matches[2]);
                $em->persist($lsItem);

                $thisLevel = $levels[$class];
                for ($i = $lastLevel; $i >= $thisLevel; --$i) {
                    array_pop($parents);
                }
                $lsItem->addParent(end($parents));

                $parents[] = $lsItem;
                $lastLevel = $thisLevel;
            } else {
                $output->writeln("<error>Not formed right -- {$text}</error>");

                return 1;
            }
        }

        $em->flush();

        $output->writeln('Command result.');
    }

}
