<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename package to Neos.EventSourcing
 */
class Version20161216132312 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Rename package to Neos.EventSourcing';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('RENAME TABLE neos_cqrs_processmanager_state_processstate TO neos_eventsourcing_processmanager_state_processstate');
        $this->addSql('RENAME TABLE neos_cqrs_eventlistener_appliedeventslog TO neos_eventsourcing_eventlistener_appliedeventslog');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('RENAME TABLE neos_eventsourcing_processmanager_state_processstate TO neos_cqrs_processmanager_state_processstate');
        $this->addSql('RENAME TABLE neos_eventsourcing_eventlistener_appliedeventslog TO neos_cqrs_eventlistener_appliedeventslog');
    }
}
