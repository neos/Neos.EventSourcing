<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename process manager state to process state
 */
class Version20161118131506 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Rename process manager state to process state';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('RENAME TABLE neos_cqrs_processmanager_state_state TO neos_cqrs_processmanager_state_processstate');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('RENAME TABLE neos_cqrs_processmanager_state_processstate TO neos_cqrs_processmanager_state_state');
    }
}
