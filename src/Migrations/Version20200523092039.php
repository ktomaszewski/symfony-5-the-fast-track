<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200523092039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds slug to conference';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE conference ADD slug VARCHAR(255)');
        $this->addSql("UPDATE conference SET slug = CONCAT(LOWER(city), '-', year)");
        $this->addSql('ALTER TABLE conference ALTER COLUMN slug SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE conference DROP slug');
    }
}
