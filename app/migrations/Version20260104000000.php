<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260104000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make telegram_id nullable and prepare for removal';
    }

    public function up(Schema $schema): void
    {
        // Make telegram_id nullable first
        $this->addSql('ALTER TABLE user MODIFY telegram_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert to NOT NULL (this may fail if there are NULL values)
        $this->addSql('ALTER TABLE user MODIFY telegram_id VARCHAR(255) NOT NULL');
    }
}
