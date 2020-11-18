<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Process Manager State
 */
class Version20161116120502 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Process Manager State';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('CREATE TABLE neos_cqrs_processmanager_state_state (identifier VARCHAR(40) NOT NULL, processmanagerclassname VARCHAR(255) NOT NULL, properties LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', checklist LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(identifier, processmanagerclassname)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('DROP TABLE neos_cqrs_processmanager_state_state');
    }
}
