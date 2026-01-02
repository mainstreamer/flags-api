<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101230929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE answer (id INT AUTO_INCREMENT NOT NULL, timer INT NOT NULL, flag_code VARCHAR(255) NOT NULL, answer_options VARCHAR(255) NOT NULL, correct TINYINT NOT NULL, date DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_DADD4A25A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE capital (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, region VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE capitals_stat (id INT AUTO_INCREMENT NOT NULL, session_timer INT NOT NULL, score INT NOT NULL, game_type VARCHAR(255) NOT NULL, created DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_177BBE19A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE flag (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, shows INT NOT NULL, correct_guesses INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, questions JSON NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_232B318CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE score (id INT AUTO_INCREMENT NOT NULL, session_timer INT NOT NULL, score INT NOT NULL, date DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, telegram_id VARCHAR(255) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, telegram_username VARCHAR(255) DEFAULT NULL, telegram_photo_url VARCHAR(255) DEFAULT NULL, high_score INT NOT NULL, games_total INT NOT NULL, best_time INT NOT NULL, time_total INT NOT NULL, sub VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A25A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE capitals_stat ADD CONSTRAINT FK_177BBE19A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A25A76ED395');
        $this->addSql('ALTER TABLE capitals_stat DROP FOREIGN KEY FK_177BBE19A76ED395');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CA76ED395');
        $this->addSql('DROP TABLE answer');
        $this->addSql('DROP TABLE capital');
        $this->addSql('DROP TABLE capitals_stat');
        $this->addSql('DROP TABLE flag');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE score');
        $this->addSql('DROP TABLE user');
    }
}
