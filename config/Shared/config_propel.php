<?php

declare(strict_types = 1);

use Spryker\Shared\Propel\PropelConstants;
use Spryker\Zed\Propel\PropelConfig;
use Spryker\Zed\PropelOrm\Business\Builder\ExtensionObjectBuilder;
use Spryker\Zed\PropelOrm\Business\Builder\ExtensionQueryBuilder;
use Spryker\Zed\PropelOrm\Business\Builder\ObjectBuilder;
use Spryker\Zed\PropelOrm\Business\Builder\QueryBuilder;

$placeholder = '%s:host=%s;port=%d;dbname=%s';

$dsn = sprintf(
    $placeholder,
    $config[PropelConstants::ZED_DB_ENGINE],
    $config[PropelConstants::ZED_DB_HOST],
    $config[PropelConstants::ZED_DB_PORT],
    $config[PropelConstants::ZED_DB_DATABASE],
);

// Connection init settings, applied by the adapter on every newly opened connection.
// Propel does NOT inherit these from the primary to the replicas
// (\Propel\Runtime\Connection\ConnectionManagerPrimaryReplica::getReadConnection() uses each
// replica's own configuration verbatim), so the same array is attached to both below —
// otherwise read connections silently run with the server defaults.
$settings = [
    PropelConfig::DB_ENGINE_PGSQL => [],
    PropelConfig::DB_ENGINE_MYSQL => [
        'charset' => 'utf8',
        'queries' => [
            'utf8' => 'SET NAMES utf8 COLLATE utf8_unicode_ci, COLLATION_CONNECTION = utf8_unicode_ci, COLLATION_DATABASE = utf8_unicode_ci, COLLATION_SERVER = utf8_unicode_ci',
            // MariaDB 11.8 only — REMOVE this line when running an older MariaDB version.
            // The variable was introduced in 11.6.2, where it also became ON by default, and
            // older servers reject the statement with "Unknown system variable".
            //
            // With it ON, a locking read (SELECT ... FOR UPDATE) fails with ER_CHECKREAD, 1020,
            // "Record has changed since last read", when BOTH hold: the transaction already took
            // its snapshot on an earlier read, and the locked row was committed by someone else
            // after that snapshot. A locking read that is the first statement of its transaction
            // never trips this, which is why the lock in CheckoutWorkflow::doSaveOrder() is the
            // first thing its transaction does. Anything reached further inside an open
            // transaction — a nested handleDatabaseTransaction() joins the outer one rather than
            // starting its own — is exposed.
            //
            // It cannot be switched off globally on RDS: SET GLOBAL needs SUPER, which the
            // instance user does not have, it is not exposed as an RDS parameter, and a global
            // value would not survive a reboot or failover anyway. Hence per session.
            'snapshot_isolation' => 'SET SESSION innodb_snapshot_isolation = OFF',
        ],
    ],
];

$slaves = [];
foreach ($config[PropelConstants::ZED_DB_REPLICAS] ?? [] as $slaveData) {
    $slaves[] = [
        'dsn' => sprintf(
            $placeholder,
            $config[PropelConstants::ZED_DB_ENGINE],
            $slaveData[PropelConstants::ZED_DB_HOST],
            $slaveData[PropelConstants::ZED_DB_PORT],
            $config[PropelConstants::ZED_DB_DATABASE],
        ),
        'user' => $config[PropelConstants::ZED_DB_USERNAME],
        'password' => $config[PropelConstants::ZED_DB_PASSWORD],
        'settings' => $settings[$config[PropelConstants::ZED_DB_ENGINE]],
    ];
}

$connections = [
    PropelConfig::DB_ENGINE_PGSQL => [
        'adapter' => PropelConfig::DB_ENGINE_PGSQL,
        'dsn' => $dsn,
        'user' => $config[PropelConstants::ZED_DB_USERNAME],
        'password' => $config[PropelConstants::ZED_DB_PASSWORD],
        'slaves' => $slaves,
        'settings' => $settings[PropelConfig::DB_ENGINE_PGSQL],
    ],
    PropelConfig::DB_ENGINE_MYSQL => [
        'adapter' => PropelConfig::DB_ENGINE_MYSQL,
        'dsn' => $dsn,
        'user' => $config[PropelConstants::ZED_DB_USERNAME],
        'password' => $config[PropelConstants::ZED_DB_PASSWORD],
        'slaves' => $slaves,
        'settings' => $settings[PropelConfig::DB_ENGINE_MYSQL],
    ],
];

$config[PropelConstants::PROPEL] = [
    'database' => [
        'connections' => [],
    ],
    'runtime' => [
        'defaultConnection' => 'default',
        'connections' => ['default', 'zed'],
    ],
    'generator' => [
        'defaultConnection' => 'default',
        'connections' => ['default', 'zed'],
        'objectModel' => [
            'defaultKeyType' => 'fieldName',
            'builders' => [
                // If you need full entity logging on Create/Update/Delete, then switch to
                // Spryker\Zed\PropelOrm\Business\Builder\ObjectBuilderWithLogger instead.
                'object' => ObjectBuilder::class,
                'objectstub' => ExtensionObjectBuilder::class,
                'query' => QueryBuilder::class,
                'querystub' => ExtensionQueryBuilder::class,
            ],
        ],
    ],
    'paths' => [
        'phpDir' => APPLICATION_ROOT_DIR,
        'sqlDir' => APPLICATION_ROOT_DIR . '/src/Orm/Propel/Sql',
        'migrationDir' => APPLICATION_ROOT_DIR . '/src/Orm/Propel/Migration_' . $config[PropelConstants::ZED_DB_ENGINE],
        'schemaDir' => APPLICATION_ROOT_DIR . '/src/Orm/Propel/Schema',
    ],
];

$ENGINE = $config[PropelConstants::ZED_DB_ENGINE];
$config[PropelConstants::PROPEL]['database']['connections']['default'] = $connections[$ENGINE];
$config[PropelConstants::PROPEL]['database']['connections']['zed'] = $connections[$ENGINE];
