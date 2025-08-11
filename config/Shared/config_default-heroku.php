<?php

// Heroku RDS configuration
use Spryker\Shared\Propel\PropelConstants;
use Spryker\Shared\RabbitMq\RabbitMqEnv;
use Spryker\Shared\SecurityBlocker\SecurityBlockerConstants;
use Spryker\Shared\SessionRedis\SessionRedisConstants;
use Spryker\Shared\Storage\StorageConstants;
use Spryker\Shared\StorageRedis\StorageRedisConstants;

require 'config_default-docker.production.php';

$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $url = parse_url($databaseUrl);
    $config[PropelConstants::ZED_DB_HOST] = $url['host'];
    $config[PropelConstants::ZED_DB_PORT] = $url['port'];
    $config[PropelConstants::ZED_DB_USERNAME] = $url['user'];
    $config[PropelConstants::ZED_DB_PASSWORD] = $url['pass'];
    $config[PropelConstants::ZED_DB_DATABASE] = ltrim($url['path'] ?? '', '/');
}

// Heroku Redis configuration
$redisUrl = getenv('REDIS_URL');
if ($redisUrl) {
    $url = parse_url($redisUrl);
    $options = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
    $config[SessionRedisConstants::ZED_SESSION_REDIS_SCHEME] = $url['scheme'];
    $config[SessionRedisConstants::ZED_SESSION_REDIS_HOST] = $url['host'];
    $config[SessionRedisConstants::ZED_SESSION_REDIS_PORT] = $url['port'];
    $config[SessionRedisConstants::ZED_SESSION_REDIS_PASSWORD] = $url['pass'];
    $config[SessionRedisConstants::ZED_SESSION_REDIS_CLIENT_OPTIONS] = $options;
    $config[SessionRedisConstants::ZED_SESSION_REDIS_DATABASE] = false;


    $config[SessionRedisConstants::YVES_SESSION_REDIS_SCHEME] = $url['scheme'];
    $config[SessionRedisConstants::YVES_SESSION_REDIS_HOST] = $url['host'];
    $config[SessionRedisConstants::YVES_SESSION_REDIS_PORT] = $url['port'];
    $config[SessionRedisConstants::YVES_SESSION_REDIS_PASSWORD] = $url['pass'];
    $config[SessionRedisConstants::YVES_SESSION_REDIS_DATABASE] = false;
    $config[SessionRedisConstants::YVES_SESSION_REDIS_CLIENT_OPTIONS] = $options;


    $config[StorageConstants::STORAGE_KV_SOURCE] = 'redis';
    $config[StorageRedisConstants::STORAGE_REDIS_PERSISTENT_CONNECTION] = true;
    $config[StorageRedisConstants::STORAGE_REDIS_SCHEME] = $url['scheme'];
    $config[StorageRedisConstants::STORAGE_REDIS_HOST] = $url['host'];
    $config[StorageRedisConstants::STORAGE_REDIS_PORT] = $url['port'];
    $config[StorageRedisConstants::STORAGE_REDIS_PASSWORD] = $url['pass'];
    $config[StorageRedisConstants::STORAGE_REDIS_DATABASE] = false;
    $config[StorageRedisConstants::STORAGE_REDIS_CONNECTION_OPTIONS] = $options;

    $config[SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_SCHEME] = $url['scheme'];
    $config[SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_HOST] = $url['host'];
    $config[SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PORT] = $url['port'];
    $config[SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PASSWORD] = $url['pass'];
    $config[SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_DATABASE] = false;
    $config[SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_CONNECTION_OPTIONS] = $options;
}

// Heroku RMQ configuration
$rmqUrl = getenv('CLOUDAMQP_CHARCOAL_URL');
$rmqApiKey = getenv('CLOUDAMQP_CHARCOAL_APIKEY');
if ($rmqUrl) {
    $url = parse_url($rmqUrl);

    $config[RabbitMqEnv::RABBITMQ_API_HOST] = $url['host'];
    $config[RabbitMqEnv::RABBITMQ_API_PORT] = $url['port'] ?? 5672;
    $config[RabbitMqEnv::RABBITMQ_API_USERNAME] = $url['user'];
    $config[RabbitMqEnv::RABBITMQ_API_PASSWORD] = $url['pass'] ?? $rmqApiKey;
    $config[RabbitMqEnv::RABBITMQ_API_VIRTUAL_HOST] = getenv('SPRYKER_CURRENT_REGION');

    $defaultConnection = [
        RabbitMqEnv::RABBITMQ_HOST => $url['host'],
        RabbitMqEnv::RABBITMQ_PORT => $url['port'] ?? 5672,
        RabbitMqEnv::RABBITMQ_USERNAME => $url['user'],
        RabbitMqEnv::RABBITMQ_PASSWORD => $url['pass'],
        RabbitMqEnv::RABBITMQ_CONNECTION_NAME => 'default-connection',
        RabbitMqEnv::RABBITMQ_DEFAULT_CONNECTION => true,
    ];
    $defaultKey = getenv('SPRYKER_CURRENT_REGION');

    $config[RabbitMqEnv::RABBITMQ_CONNECTIONS] = [];
    $config[RabbitMqEnv::RABBITMQ_CONNECTIONS][$defaultKey] = $defaultConnection;
}
