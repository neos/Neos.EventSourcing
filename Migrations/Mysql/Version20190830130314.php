<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20190830130314 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Drop obsolete "processstate" table';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws DBALException | AbortMigrationException
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE neos_eventsourcing_processmanager_state_processstate');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws DBALException | AbortMigrationException
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE neos_eventsourcing_processmanager_state_processstate (identifier VARCHAR(40) NOT NULL COLLATE utf8_unicode_ci, processmanagerclassname VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, properties LONGTEXT NOT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:array)\', checklist LONGTEXT NOT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:array)\', PRIMARY KEY(identifier, processmanagerclassname)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
    }
}
