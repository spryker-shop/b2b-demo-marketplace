<?php

declare(strict_types = 1);

use Pyz\Client\SymfonyScheduler\SymfonySchedulerConfig;

/**
 * Notes:
 *
 * - jobs[]['name'] must not contains spaces or any other characters, that have to be urlencode()'d
 * - jobs[]['role'] default value is 'admin'
 */

//$logger = 'config/Zed/cronjobs/bin/loggable.sh '; // script for jenkins logging
$logger = '';

// Legacy setup: Symfony Scheduler/Messenger is switched off, so Jenkins has to run every cron job
// on its own instead of consuming the compiled Symfony Messenger transports.
foreach ((new SymfonySchedulerConfig())->getCronJobs() as $jobName => $cronJob) {
    $jobs[] = [
        'name' => $jobName,
        'command' => $cronJob['command'],
        'schedule' => $cronJob['schedule'],
        'enable' => true,
    ];
}

if (getenv('SPRYKER_CURRENT_REGION')) {
    foreach ($jobs as $job) {
        $job['region'] = getenv('SPRYKER_CURRENT_REGION');
    }
}
