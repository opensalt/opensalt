<?php

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use \Codeception\Util\Stub;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use GithubFilesBundle\Service\GithubImport;
use Salt\SiteBundle\Entity\CommentUpvote;

class GithubImportTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $validItemKeys = [
        'identifier'            =>    'Identifier',
        'fullStatement'         =>    'fullStatement',
        'humanCodingScheme'     =>    'Human+Coding Scheme',
        'abbreviatedStatement'  =>    'Abbreviated Statement',
        'conceptKeywords'       =>    'ConceptKeywords',
        'notes'                 =>    'Notes',
        'language'              =>    'Language',
        'educationLevel'        =>    'educationLevel',
        'cfItemType'            =>    'CFItemType',
        'license'               =>    'License',
        'isChildOf'             =>    'Is Child Of',
        'isPartOf'              =>    'IsPartOf',
        'replacedBy'            =>    'replacedBy',
        'exemplar'              =>    'Exemplar',
        'hasSkillLevel'         =>    'hasSkillLevel',
        'isRelatedTo'           =>    'IsRelatedTo'
    ];
    protected $validCSVContent = <<<EOT
Identifier,fullStatement,Human Coding Scheme,Abbreviated Statement,ConceptKeywords,Notes,Language,educationLevel,CFItemType,License,CFAssociationGroupIdentifier,Is Child Of,IsPartOf,replacedBy,Exemplar,hasSkillLevel,IsRelatedTo
38ce84d0-87de-4937-b030-b1f1eab03ce0,"Perceptions of one's own abilities, interests, skills, values, attitudes, beliefs, etc. that contribute to understanding the self",H.N.SK,Self-Knowledge,,,en,,,,,H.N,,,,,
de5aa87c-c344-4a36-ae61-498b083a324b,"States of perceiving, feeling, or being conscious of oneself, education, work, and the gaps among them",H.N.SK.AW,Awareness,,,en,,,,,H.N.SK,,,,,
c37224f3-0d6d-4bee-bf1e-66de6591da48,"state in which an individual is able to recognize oneself as an individual in relationship to others and the environment; there is a capacity for introspection and becoming aware of one's traits, feelings, and behavior",H.N.SK.AW.SAW,Self-Awareness,,,en,,,,,H.N.SK.AW,,,,,
2b88ba69-d07e-4ff0-92f5-8bec2b056a85,"Recognize that people have similarities and differences in their interests, values, and skills",H.N.SK.AW.SAW.1,,,,en,,,,,H.N.SK.AW.SAW,,,,,38ce84d0-87de-4937-b030-b1f1eab03ce0
50159f69-74ed-41a6-b6f2-9ba61d5678d9,"Recognize that trying new things can help you to better understand your interests, values, and skills",H.N.SK.AW.SAW.2,,,,en,,,,,H.N.SK.AW.SAW,,,,,
d61aa556-b961-4a54-9775-0f50a12d527b,Learn how to keep track of the tasks you need to do for school and how well you did them,H.N.SK.AW.SAW.3,,,,en,,,,,H.N.SK.AW.SAW,,,,,
0bd6c580-8cc8-45eb-a6b4-ed9f02bb0b13,"Learn how your personal interests, values, and skills can relate to the education plan you build for high school",H.N.SK.AW.SAW.4,,,,en,,,,,H.N.SK.AW.SAW,,,,,
239c8a41-df9d-420c-a881-1e8946683a84,Recognize that initial education or occupation choices can and will likely change,H.N.SK.AW.SAW.5,,,,en,,,,,H.N.SK.AW.SAW,,,,,
48e016cc-ca5b-464e-9e87-bc18ca633d49,"Learn how your interests, values, and skills are connected to your decisions to enroll in certain electives when developing your high school class schedule",H.N.SK.AW.SAW.6,,,,en,,,,,H.N.SK.AW.SAW,,,,,
98f62df-4065-48b5-8a65-f6c6fb49b686,Recognize that choices about your postsecondary education or occupation can change through additional exploration,H.N.SK.AW.SAW.7,,,,en,,,,,H.N.SK.AW.SAW,,,,,
EOT;
    // I used a custom and uniq fullStatement to check item does not be created with the identifier already persisted
    protected $repeatedItemOnCSV = <<<EOT
Identifier,fullStatement,Human Coding Scheme,Abbreviated Statement,ConceptKeywords,Notes,Language,educationLevel,CFItemType,License,CFAssociationGroupIdentifier,Is Child Of,IsPartOf,replacedBy,Exemplar,hasSkillLevel,IsRelatedTo
38ce84d0-87de-4937-b030-b1f1eab03ce0,"RYcknN3uf9nFcah5bdEg7tuyPnRtxXBFvnQXAYCP8jyCsQ3NYrJEz2smDwkJsVydp9etRmC7zwySGWEgufaGgs4CwYtqEdvPY4jeQx73H3k8wY9hYa4RNwbUaph8hZYt",H.N.SK,Self-Knowledge,,,en,,,,,H.N,,,,,
de5aa87c-c344-4a36-ae61-498b083a324b,"aHq97MnW2sEAn5LgCByW7K8tVu6gBPqck6QmKHbfYu4m2FE42UWkDpmcyapeW6ghgxsVNRdWJKL2dxUKzUtsFdpaUYDFzM9CrYdXmaZkkUjc4uyCtF54rG2Ne5Jy7trF",H.N.SK.AW,Awareness,,,en,,,,,H.N.SK,,,,,
EOT;
    protected $managerRegistry;
    /** @var EntityManager */
    protected $em;
    protected $lsDoc;

    public function _before(){
        $this->managerRegistry = Stub::makeEmpty(
            'Doctrine\Common\Persistence\ManagerRegistry',
            array(
                'getManagerForClass' => function() { return $this->em; }
            )
        );
        $this->tester->ensureUserExistsWithRole('Editor');
        $this->em = $this->getModule('Doctrine2')->em;
        $this->lsDoc = new LsDoc();
        $this->lsDoc->setTitle('LsDoc Tested');
        $this->lsDoc->setCreator('GithubImporter Tester');
        $this->em->persist($this->lsDoc);
        $this->em->flush();

    }

    public function testSaveItem(){
        $githubImporter = new GithubImport($this->managerRegistry);
        $githubImporter->parseCSVGithubDocument($this->validItemKeys, $this->validCSVContent, $this->lsDoc->getId(), 'all', []);
        $this->em->flush();

        $dataToSeeInDatabase = [
            ['identifier' => '38ce84d0-87de-4937-b030-b1f1eab03ce0'],
            ['identifier' => '48e016cc-ca5b-464e-9e87-bc18ca633d49'],
            ['identifier' => '2b88ba69-d07e-4ff0-92f5-8bec2b056a85']
        ];
        foreach($dataToSeeInDatabase as $dataToSee){
            $this->tester->seeInRepository(LsItem::class, $dataToSee);
        }
    }

    public function testSaveItemAssociations(){
        $githubImporter = new GithubImport($this->managerRegistry);
        $githubImporter->parseCSVGithubDocument($this->validItemKeys, $this->validCSVContent, $this->lsDoc->getId(), 'all', []);
        $this->em->flush();

        $lsItemToCheck = $this->em->getRepository(LsItem::class)->findOneBy(array('identifier' => '2b88ba69-d07e-4ff0-92f5-8bec2b056a85'));
        // associations include its inverse associations
        $this->assertEquals(9, count((array)$lsItemToCheck->getAssociations()));
    }
}
