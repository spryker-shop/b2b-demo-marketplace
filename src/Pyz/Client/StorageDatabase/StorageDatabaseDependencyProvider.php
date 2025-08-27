<?php

namespace Pyz\Client\StorageDatabase;

use Spryker\Client\StorageDatabase\Plugin\PostgreSqlStorageReaderPlugin;
use Spryker\Client\StorageDatabase\StorageDatabaseDependencyProvider as SprykerStorageDatabaseDependencyProvider;
use Spryker\Client\StorageDatabaseExtension\Dependency\Plugin\StorageReaderPluginInterface;

class StorageDatabaseDependencyProvider extends SprykerStorageDatabaseDependencyProvider
{
    protected function getStorageReaderProviderPlugin(): StorageReaderPluginInterface
    {
        return new PostgreSqlStorageReaderPlugin();
    }
}
