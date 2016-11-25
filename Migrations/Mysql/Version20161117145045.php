<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Drop projection state and introduce generic applied events log
 */
class Version20161117145045 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Drop projection state and introduce generic applied events log';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE neos_cqrs_eventlistener_appliedeventslog (eventlisteneridentifier VARCHAR(255) NOT NULL, highestappliedsequencenumber INT NOT NULL, PRIMARY KEY(eventlisteneridentifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE neos_cqrs_projection_doctrine_projectionstate');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE neos_cqrs_projection_doctrine_projectionstate (projectoridentifier VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, highestappliedsequencenumber INT NOT NULL, PRIMARY KEY(projectoridentifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE neos_cqrs_eventlistener_appliedeventslog');
    }
}
