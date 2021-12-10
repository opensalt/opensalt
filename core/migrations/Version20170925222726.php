<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170925222726 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salt_comment_upvote DROP FOREIGN KEY FK_4DB1D19CF8697D13');
        $this->addSql('ALTER TABLE salt_comment_upvote ADD CONSTRAINT FK_4DB1D19CF8697D13 FOREIGN KEY (comment_id) REFERENCES salt_comment (id) ON DELETE CASCADE');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salt_comment_upvote DROP FOREIGN KEY FK_4DB1D19CF8697D13');
        $this->addSql('ALTER TABLE salt_comment_upvote ADD CONSTRAINT FK_4DB1D19CF8697D13 FOREIGN KEY (comment_id) REFERENCES salt_comment (id)');
    }
}
