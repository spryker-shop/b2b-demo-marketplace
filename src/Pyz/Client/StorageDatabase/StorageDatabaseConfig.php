<?php

namespace Pyz\Client\StorageDatabase;

use Spryker\Client\StorageDatabase\StorageDatabaseConfig as SprykerStorageDatabaseConfig;
use Spryker\Shared\StorageDatabase\StorageDatabaseConfig as SharedStorageDatabaseConfig;

class StorageDatabaseConfig extends SprykerStorageDatabaseConfig
{
    public function getResourceNameToStorageTableMap(): array
    {
        return [
            'translation' => [
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_NAME => 'glossary',
            ],
            'product_search_config_extension' => [
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_PREFIX => 'spy',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_NAME => 'product_search_config',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_SUFFIX => 'storage',
            ],
            'redirect' => [
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_PREFIX => 'spy',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_NAME => 'url_redirect',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_SUFFIX => 'storage',
            ],
            'product_abstract_tax_set' => [
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_PREFIX => 'spy',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_NAME => 'tax_product',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_SUFFIX => 'storage',
            ],
            'product_abstract_product_lists' => [
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_PREFIX => 'spy',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_NAME => 'product_abstract_product_list',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_SUFFIX => 'storage',
            ],
            'product_concrete_product_lists' => [
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_PREFIX => 'spy',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_NAME => 'product_concrete_product_list',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_SUFFIX => 'storage',
            ],
            'search_http_config' => [
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_PREFIX => 'spy',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_NAME => 'search_http_config',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_SUFFIX => '',
            ],
            'tenant' => [
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_PREFIX => 'pyz',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_NAME => 'tenant',
                SharedStorageDatabaseConfig::KEY_STORAGE_TABLE_SUFFIX => '',
            ],
        ];
    }
}
