<?php

namespace Pyz\Client\StorageDatabase;

use Pyz\Client\StorageDatabase\Storage\Reader\MySqlStorageReader;
use Pyz\Client\StorageDatabase\Storage\Reader\PostgreSqlStorageReader;
use Spryker\Client\StorageDatabase\Storage\Reader\StorageReaderInterface;
use Spryker\Client\StorageDatabase\StorageDatabaseFactory as SprykerStorageDatabaseFactory;

class StorageDatabaseFactory extends SprykerStorageDatabaseFactory
{
    public function createMySqlStorageReader(): StorageReaderInterface
    {
        return new MySqlStorageReader(
            $this->createConnectionProvider(),
            $this->createStorageTableNameResolver(),
        );
    }

    /**
     * @return \Spryker\Client\StorageDatabase\Storage\Reader\StorageReaderInterface
     */
    public function createPostgreSqlStorageReader(): StorageReaderInterface
    {
        return new PostgreSqlStorageReader(
            $this->createConnectionProvider(),
            $this->createStorageTableNameResolver(),
        );
    }
}
