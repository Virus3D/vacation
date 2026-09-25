<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260925041030 extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Add non_accrual_period';
    }// end getDescription()

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE non_accrual_period (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                type VARCHAR(50) NOT NULL,
                comment VARCHAR(255) DEFAULT NULL,
                employee_id INTEGER NOT NULL,
                CONSTRAINT FK_C9FAE8358C03F15C FOREIGN KEY (employee_id)
                REFERENCES employee (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )'
        );
        $this->addSql('CREATE INDEX IDX_C9FAE8358C03F15C ON non_accrual_period (employee_id)');
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE non_accrual_period');
    }// end down()
}// end class
