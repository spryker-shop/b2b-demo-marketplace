<?php

declare(strict_types = 1);

/**
 * Internal switch between the two supported infrastructure setups, so CI can still run the legacy one.
 *
 * `false` - legacy setup, the one customers that have not migrated yet still run: the RabbitMQ queue
 * adapter, and Jenkins registering every cron job itself.
 * `true` - Symfony Messenger as the queue adapter, with Symfony Scheduler owning the cron jobs.
 *
 * Must be pulled in with `require`, not `require_once` - it is read by more than one config file per process.
 */
return true;
