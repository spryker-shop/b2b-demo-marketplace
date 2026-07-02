<?php

declare(strict_types = 1);

use SprykerEco\Shared\AmazonQuicksight\AmazonQuicksightConstants;

// -------------------------------- AWS QUICKSIGHT -------------------------------

$config[AmazonQuicksightConstants::AWS_ACCOUNT_ID] = getenv('AWS_ACCOUNT_ID');
$config[AmazonQuicksightConstants::AWS_REGION] = getenv('AWS_REGION') ?: 'eu-central-1';
$config[AmazonQuicksightConstants::AWS_QUICKSIGHT_NAMESPACE] = getenv('QUICKSIGHT_NAMESPACE');
$config[AmazonQuicksightConstants::DEFAULT_DATA_SOURCE_USERNAME] = getenv('SPRYKER_BI_DB_USER') ?: getenv('SPRYKER_DB_USERNAME');
$config[AmazonQuicksightConstants::DEFAULT_DATA_SOURCE_PASSWORD] = getenv('SPRYKER_BI_DB_PASSWORD') ?: getenv('SPRYKER_DB_PASSWORD');
$config[AmazonQuicksightConstants::DEFAULT_DATA_SOURCE_DATABASE_NAME] = getenv('SPRYKER_DB_DATABASE');
$config[AmazonQuicksightConstants::DEFAULT_DATA_SOURCE_DATABASE_HOST] = getenv('SPRYKER_DB_RO_REPLICA_HOST');
$config[AmazonQuicksightConstants::DEFAULT_DATA_SOURCE_DATABASE_PORT] = getenv('SPRYKER_DB_PORT');
$config[AmazonQuicksightConstants::DEFAULT_DATA_SOURCE_VPC_CONNECTION_ARN] = getenv('QUICKSIGHT_VPC_CONNECTION_ARN');
$config[AmazonQuicksightConstants::GENERATE_EMBED_URL_ALLOWED_DOMAINS] = [
    sprintf('https://%s', getenv('SPRYKER_BE_HOST')),
];
$config[AmazonQuicksightConstants::QUICKSIGHT_ASSUMED_ROLE_ARN] = getenv('QUICKSIGHT_ASSUMED_ROLE_ARN');
