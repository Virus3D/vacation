<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928055927 extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Add termination date';
    }// end getDescription()

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee ADD COLUMN termination_date DATE DEFAULT NULL');
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__employee AS
            SELECT id, full_name, hire_date, base_vacation_days, additional_vacation_days, max_seniority_additional_days
            FROM employee'
        );
        $this->addSql('DROP TABLE employee');
        $this->addSql(
            'CREATE TABLE employee (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                hire_date DATE NOT NULL,
                base_vacation_days INTEGER NOT NULL,
                additional_vacation_days INTEGER NOT NULL,
                max_seniority_additional_days INTEGER DEFAULT 10 NOT NULL
            )'
        );
        $this->addSql(
            'INSERT INTO employee
            (id, full_name, hire_date, base_vacation_days, additional_vacation_days, max_seniority_additional_days)
            SELECT id, full_name, hire_date, base_vacation_days, additional_vacation_days, max_seniority_additional_days
            FROM __temp__employee'
        );
        $this->addSql('DROP TABLE __temp__employee');
    }// end down()
}// end class
