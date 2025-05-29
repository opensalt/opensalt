<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Ramsey\Uuid\Uuid;

class Version20160928191216 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $sql = <<<'xENDx'
SELECT d.id, d.subject, d.subject_uri
  FROM ls_doc d
 WHERE d.subject IS NOT NULL OR d.subject_uri IS NOT NULL
xENDx;
        $docsStmt = $this->connection->prepare($sql);

        $sql = <<<'xENDx'
INSERT IGNORE INTO ls_def_subject
  (identifier, uri, updated_at, title, hierarchy_code)
VALUES
  (:uuid, :uri, NOW(), :title, :hierarchy)
xENDx;
        $insertSubjectStmt = $this->connection->prepare($sql);

        $sql = <<<'xENDx'
INSERT IGNORE INTO ls_doc_subject
  (ls_doc_id, subject_id)
VALUES
  (:doc_id, :subj_id)
xENDx;
        $insertDocSubjectStmt = $this->connection->prepare($sql);

        $sql = <<<'xENDx'
SELECT s.id, s.title
  FROM ls_def_subject s
 WHERE s.identifier = :uuid
xENDx;
        $fetchStmt = $this->connection->prepare($sql);

        $docs = $docsStmt->executeQuery()->fetchAllAssociative();
        $subjects = [];
        foreach ($docs as $doc) {
            if (empty($doc['subject'])) {
                $subject = ucfirst(preg_replace('#.*/#', '', $doc['subject_uri']));
            } else {
                $subject = $doc['subject'];
            }

            if (!array_key_exists($subject, $subjects)) {
                $uuid = Uuid::uuid5(Uuid::fromString('cacee394-85b7-11e6-9d43-005056a32dda'), $subject);
                $insertSubjectStmt->bindValue('uuid', $uuid->toString());
                $insertSubjectStmt->bindValue('uri', 'local:'.$uuid->toString());
                $insertSubjectStmt->bindValue('title', $subject);
                $insertSubjectStmt->bindValue('hierarchy', 1, ParameterType::INTEGER);
                $insertSubjectStmt->executeStatement();

                $fetchStmt->bindValue('uuid', $uuid->toString());
                $s = $fetchStmt->executeQuery()->fetchOne();

                $subjects[$subject] = $s;
            } else {
                $s = $subjects[$subject];
            }

            $insertDocSubjectStmt->bindValue('doc_id', $doc['id'], ParameterType::INTEGER);
            $insertDocSubjectStmt->bindValue('subj_id', $s['id'], ParameterType::INTEGER);
            $insertDocSubjectStmt->executeStatement();
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Cannot revert');
    }
}
