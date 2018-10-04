<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181004091617 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE place (id INT AUTO_INCREMENT NOT NULL, horaire_id_id INT NOT NULL, class VARCHAR(255) NOT NULL, reserved TINYINT(1) NOT NULL, INDEX IDX_741D53CD1AAC298C (horaire_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE destination (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE horaire (id INT AUTO_INCREMENT NOT NULL, from_id_id INT NOT NULL, to_id_id INT NOT NULL, depart_at DATETIME NOT NULL, arrive_at DATETIME NOT NULL, INDEX IDX_BBC83DB64632BB48 (from_id_id), INDEX IDX_BBC83DB67478AF67 (to_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE place ADD CONSTRAINT FK_741D53CD1AAC298C FOREIGN KEY (horaire_id_id) REFERENCES horaire (id)');
        $this->addSql('ALTER TABLE horaire ADD CONSTRAINT FK_BBC83DB64632BB48 FOREIGN KEY (from_id_id) REFERENCES destination (id)');
        $this->addSql('ALTER TABLE horaire ADD CONSTRAINT FK_BBC83DB67478AF67 FOREIGN KEY (to_id_id) REFERENCES destination (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE horaire DROP FOREIGN KEY FK_BBC83DB64632BB48');
        $this->addSql('ALTER TABLE horaire DROP FOREIGN KEY FK_BBC83DB67478AF67');
        $this->addSql('ALTER TABLE place DROP FOREIGN KEY FK_741D53CD1AAC298C');
        $this->addSql('DROP TABLE place');
        $this->addSql('DROP TABLE destination');
        $this->addSql('DROP TABLE horaire');
    }
}
