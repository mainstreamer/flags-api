<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260104000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove telegram_id column from user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN telegram_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD telegram_id VARCHAR(255) DEFAULT NULL');
    }
}
