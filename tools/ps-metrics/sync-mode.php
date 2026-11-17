<?php

/**
 * SOL-629: resolve the synchronization mode (contract C2, `sync_mode`) from the application itself.
 *
 * Read-only. Run inside the CLI container from the project root:
 *
 *   php tools/ps-metrics/sync-mode.php            prints one JSON object
 *   php tools/ps-metrics/sync-mode.php --control  same, plus a positive control
 *
 * Where the mode actually lives. A synchronized Propel entity decides queue versus buffer in its
 * generated sendMessage(), through a generated isDirectSyncEnabled(). The Propel behaviour writes
 * that method at build time from SynchronizationBehaviorConfig::isDirectSynchronizationEnabled(),
 * isDynamicStoreEnabled() (the SPRYKER_DYNAMIC_STORE_MODE env var) and the per-table
 * `direct_sync_disabled` parameter. So the effective mode is what the generated entities return,
 * and the config is only what the next propel:build would bake in. Both are reported, and a
 * disagreement means the generated code is stale.
 *
 * Without --control the output is the effective mode. With --control the config path is evaluated
 * a second time with SPRYKER_DYNAMIC_STORE_MODE unset, and the entity classifier is run against a
 * stub entity returning false, to show that each check can report "queue" and is not constant.
 */

declare(strict_types = 1);

$root = getcwd();
require $root . '/vendor/autoload.php';

const BEHAVIOR_CONFIG = 'Pyz\\Zed\\SynchronizationBehavior\\SynchronizationBehaviorConfig';
const CORE_BEHAVIOR_CONFIG = 'Spryker\\Zed\\SynchronizationBehavior\\SynchronizationBehaviorConfig';
const SYNCHRONIZATION_FACADE = 'Spryker\\Zed\\Synchronization\\Business\\SynchronizationFacade';

/**
 * @return array<string, mixed>
 */
function resolveConfig(): array
{
    $class = class_exists(BEHAVIOR_CONFIG) ? BEHAVIOR_CONFIG : CORE_BEHAVIOR_CONFIG;
    $config = new $class();
    $direct = $config->isDirectSynchronizationEnabled();
    $dynamicStore = $config->isDynamicStoreEnabled();

    return [
        'class' => $class,
        'is_direct_synchronization_enabled' => $direct,
        'is_dynamic_store_enabled' => $dynamicStore,
        'mode' => ($direct && $dynamicStore) ? 'direct' : 'queue',
    ];
}

/**
 * @param object $entity
 *
 * @return bool
 */
function entityIsDirect(object $entity): bool
{
    $method = new ReflectionMethod($entity, 'isDirectSyncEnabled');

    return (bool)$method->invoke($entity);
}

/**
 * @param string $root
 *
 * @return array<string, mixed>
 */
function resolveEntities(string $root): array
{
    $direct = [];
    $queue = [];
    $files = glob($root . '/src/Orm/Zed/*/Persistence/Base/*.php') ?: [];
    foreach ($files as $file) {
        if (strpos((string)file_get_contents($file), 'function isDirectSyncEnabled') === false) {
            continue;
        }
        $module = basename(dirname($file, 3));
        $class = sprintf('Orm\\Zed\\%s\\Persistence\\%s', $module, basename($file, '.php'));
        if (!class_exists($class)) {
            continue;
        }
        $entity = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        if (entityIsDirect($entity)) {
            $direct[] = $class;
        } else {
            $queue[] = $class;
        }
    }

    // The generated sendMessage() also requires the facade to offer the buffer, else it queues.
    $bufferAvailable = method_exists(SYNCHRONIZATION_FACADE, 'addSynchronizationMessageToBuffer');

    if (!$direct && !$queue) {
        $mode = null;
    } elseif (!$bufferAvailable || !$direct) {
        $mode = 'queue';
    } elseif (!$queue) {
        $mode = 'direct';
    } else {
        $mode = 'mixed';
    }

    return [
        'mode' => $mode,
        'direct_tables' => count($direct),
        'queue_tables' => count($queue),
        'queue_table_classes' => count($queue) <= 20 ? $queue : array_slice($queue, 0, 20),
        'buffer_available' => $bufferAvailable,
    ];
}

$config = resolveConfig();
$entities = resolveEntities($root);

// `mixed` is not a C2 value: a run with some tables direct is reported as direct, and the table
// counts beside it say how many were not.
$effective = $entities['mode'] === 'mixed' ? 'direct' : $entities['mode'];

$result = [
    'sync_mode' => $effective ?? $config['mode'],
    'sync_mode_source' => $effective === null ? 'config (no generated synchronized entities found)' : 'generated entities',
    'sync_mode_config' => $config,
    'sync_mode_entities' => $entities,
    'sync_mode_stale' => $effective !== null && $effective !== $config['mode'],
];

if (in_array('--control', $argv, true)) {
    $saved = getenv('SPRYKER_DYNAMIC_STORE_MODE');
    putenv('SPRYKER_DYNAMIC_STORE_MODE');
    $controlConfig = resolveConfig();
    if ($saved !== false) {
        putenv('SPRYKER_DYNAMIC_STORE_MODE=' . $saved);
    }

    $stub = new class {
        protected function isDirectSyncEnabled(): bool
        {
            return false;
        }
    };

    $result['control'] = [
        'config_mode_without_dynamic_store' => $controlConfig['mode'],
        'entity_classifier_on_stub_returning_false' => entityIsDirect($stub) ? 'direct' : 'queue',
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
