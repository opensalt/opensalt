<?php

namespace DoctrineMigrations;

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

        $this->connection->beginTransaction();

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
                $params = [
                    'uuid' => $uuid->toString(),
                    'uri' => 'local:'.$uuid->toString(),
                    'title' => $subject,
                    'hierarchy' => 1,
                ];
                $insertSubjectStmt->executeStatement($params);

                $s = $fetchStmt->executeQuery(['uuid' => $uuid])->fetchOne();

                $subjects[$subject] = $s;
            } else {
                $s = $subjects[$subject];
            }

            $insertDocSubjectStmt->executeStatement(['doc_id' => $doc['id'], 'subj_id' => $s['id']]);
        }

        $this->connection->commit();
    }


    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Cannot revert');
    }
}
