<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Introduce a projection state for Doctrine-based projectors
 */
class Version20161114151203 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Introduce a projection state for Doctrine-based projectors';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('CREATE TABLE neos_cqrs_projection_doctrine_projectionstate (projectoridentifier VARCHAR(255) NOT NULL, highestappliedsequencenumber INT NOT NULL, PRIMARY KEY(projectoridentifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('DROP TABLE neos_cqrs_projection_doctrine_projectionstate');
    }
}
