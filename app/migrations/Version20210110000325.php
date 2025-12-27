<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210110000325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649AA334807');
        $this->addSql('DROP INDEX IDX_8D93D649AA334807 ON user');
        $this->addSql('ALTER TABLE user DROP answer_id');
        $this->addSql('ALTER TABLE answer ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A25A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_DADD4A25A76ED395 ON answer (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A25A76ED395');
        $this->addSql('DROP INDEX IDX_DADD4A25A76ED395 ON answer');
        $this->addSql('ALTER TABLE answer DROP user_id');
        $this->addSql('ALTER TABLE user ADD answer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649AA334807 FOREIGN KEY (answer_id) REFERENCES answer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8D93D649AA334807 ON user (answer_id)');
    }
}
