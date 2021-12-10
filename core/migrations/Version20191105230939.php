<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191105230939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow items to be removed and keep the association';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D434C423C4');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D44C0C393B');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D459C28905');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D4A002CDB7');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D434C423C4 FOREIGN KEY (origin_lsdoc_id) REFERENCES ls_doc (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D44C0C393B FOREIGN KEY (origin_lsitem_id) REFERENCES ls_item (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D459C28905 FOREIGN KEY (destination_lsdoc_id) REFERENCES ls_doc (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D4A002CDB7 FOREIGN KEY (destination_lsitem_id) REFERENCES ls_item (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D434C423C4');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D44C0C393B');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D459C28905');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D4A002CDB7');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D434C423C4 FOREIGN KEY (origin_lsdoc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D44C0C393B FOREIGN KEY (origin_lsitem_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D459C28905 FOREIGN KEY (destination_lsdoc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D4A002CDB7 FOREIGN KEY (destination_lsitem_id) REFERENCES ls_item (id)');
    }
}
