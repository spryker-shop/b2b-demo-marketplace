<?php

declare(strict_types = 1);

/**
 * Notes:
 *
 * - jobs[]['name'] must not contains spaces or any other characters, that have to be urlencode()'d
 * - jobs[]['role'] default value is 'admin'
 */

//$logger = 'config/Zed/cronjobs/bin/loggable.sh '; // script for jenkins logging
$logger = '';

$jobs[] = [
    'name' => 'consume-queue',
    'command' => $logger . '$PHP_BIN vendor/bin/console symfonymessenger:consume compiled-cron-scheduler --time-limit=3600',
    'schedule' => '* * * * *',
    'enable' => true,
];
$jobs[] = [
    'name' => 'consume-other-cron-jobs',
    'command' => $logger . '$PHP_BIN vendor/bin/console symfonymessenger:consume compiled-cron-scheduler --time-limit=3600 --exclude-from-group=queue-worker-start',
    'schedule' => '* * * * *',
    'enable' => true,
];

if (getenv('SPRYKER_CURRENT_REGION')) {
    foreach ($jobs as $job) {
        $job['region'] = getenv('SPRYKER_CURRENT_REGION');
    }
}
