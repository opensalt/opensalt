<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250416174130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subject and subjects to item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE ls_item_subject (ls_item_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_E3AA457BE27A1FD2 (ls_item_id), INDEX IDX_E3AA457B23EDC87 (subject_id), PRIMARY KEY(ls_item_id, subject_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item_subject ADD CONSTRAINT FK_E3AA457BE27A1FD2 FOREIGN KEY (ls_item_id) REFERENCES ls_item (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item_subject ADD CONSTRAINT FK_E3AA457B23EDC87 FOREIGN KEY (subject_id) REFERENCES ls_def_subject (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item ADD subject JSON DEFAULT NULL COMMENT '(DC2Type:json)' AFTER notes
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item_subject DROP FOREIGN KEY FK_E3AA457BE27A1FD2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item_subject DROP FOREIGN KEY FK_E3AA457B23EDC87
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ls_item_subject
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item DROP subject
        SQL);
    }

}
