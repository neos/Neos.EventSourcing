<?php

declare(strict_types=1);

namespace Neos\EventSourcing\Symfony\EventListener\AppliedEventsStorage;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Types\Types;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\AppliedEventsLog;

/**
 * TODO: move out of the symfony specific code to the Flow side of things.
 */
class DoctrineAppliedEventsStorageSetup
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * DoctrineAppliedEventsStorageSetup constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * @inheritdoc
     * @throws DBALException | \Throwable
     */
    public function setup(): Result
    {
        $result = new Result();
        $schemaManager = $this->connection->getSchemaManager();
        if ($schemaManager === null) {
            $result->addError(new Error('Failed to retrieve Schema Manager', 1592381759, [], 'Connection failed'));
            return $result;
        }
        try {
            $tableExists = $schemaManager->tablesExist([AppliedEventsLog::TABLE_NAME]);
        } catch (ConnectionException $exception) {
            $result->addError(new Error($exception->getMessage(), $exception->getCode(), [], 'Connection failed'));
            return $result;
        }
        if ($tableExists) {
            $result->addNotice(new Notice('Table "%s" (already exists)', null, [AppliedEventsLog::TABLE_NAME]));
        } else {
            $result->addNotice(new Notice('Creating database table "%s" in database "%s" on host %s....', null, [AppliedEventsLog::TABLE_NAME, $this->connection->getDatabase(), $this->connection->getHost()]));
        }

        $fromSchema = $schemaManager->createSchema();
        $schemaDiff = (new Comparator())->compare($fromSchema, $this->createEventStoreSchema());

        $statements = $schemaDiff->toSaveSql($this->connection->getDatabasePlatform());
        if ($statements === []) {
            $result->addNotice(new Notice('Table schema is up to date, no migration required'));
            return $result;
        }
        $this->connection->beginTransaction();
        try {
            foreach ($statements as $statement) {
                $result->addNotice(new Notice('<info>++</info> %s', null, [$statement]));
                $this->connection->exec($statement);
            }
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
        return $result;
    }

    /**
     * Creates the Doctrine schema to be compared with the current db schema for migration
     *
     * @return Schema
     */
    private function createEventStoreSchema(): Schema
    {
        $schemaConfiguration = new SchemaConfig();
        $connectionParameters = $this->connection->getParams();
        if (isset($connectionParameters['defaultTableOptions'])) {
            $schemaConfiguration->setDefaultTableOptions($connectionParameters['defaultTableOptions']);
        }
        $schema = new Schema([], [], $schemaConfiguration);
        $table = $schema->createTable(AppliedEventsLog::TABLE_NAME);

        $table->addColumn('eventlisteneridentifier', Types::STRING, ['length' => 255]);
        $table->addColumn('highestappliedsequencenumber', Types::INTEGER);

        $table->setPrimaryKey(['eventlisteneridentifier']);

        return $schema;
    }
}