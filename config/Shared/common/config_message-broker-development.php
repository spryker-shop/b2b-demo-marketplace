<?php

use Generated\Shared\Transfer\AssetAddedTransfer;
use Generated\Shared\Transfer\AssetDeletedTransfer;
use Generated\Shared\Transfer\AssetUpdatedTransfer;
use Generated\Shared\Transfer\PaymentCancelReservationFailedTransfer;
use Generated\Shared\Transfer\PaymentCancelReservationRequestedTransfer;
use Generated\Shared\Transfer\PaymentConfirmationFailedTransfer;
use Generated\Shared\Transfer\PaymentConfirmationRequestedTransfer;
use Generated\Shared\Transfer\PaymentConfirmedTransfer;
use Generated\Shared\Transfer\PaymentMethodAddedTransfer;
use Generated\Shared\Transfer\PaymentMethodDeletedTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentPreauthorizationFailedTransfer;
use Generated\Shared\Transfer\PaymentPreauthorizedTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\PaymentRefundRequestedTransfer;
use Generated\Shared\Transfer\PaymentReservationCanceledTransfer;
use Spryker\Shared\MessageBroker\MessageBrokerConstants;
use Spryker\Shared\MessageBrokerAws\MessageBrokerAwsConstants;
use Spryker\Zed\MessageBrokerAws\MessageBrokerAwsConfig;

// ----------------------------------------------------------------------------
// -------------------------------- AWS ---------------------------------------
// ----------------------------------------------------------------------------

// >>> SQS QUEUES
$storeNameReferenceMap = json_decode(html_entity_decode(getenv('STORE_NAME_REFERENCE_MAP'), ENT_QUOTES), true);

$tenantMessagesForStoreReferenceQueueDE = sprintf('tenant_messages_for_store_reference_%s.fifo', $storeNameReferenceMap['DE']);
$tenantMessagesForStoreReferenceQueueAT = sprintf('tenant_messages_for_store_reference_%s.fifo', $storeNameReferenceMap['AT']);
$tenantMessagesForStoreReferenceQueueUS = sprintf('tenant_messages_for_store_reference_%s.fifo', $storeNameReferenceMap['US']);

$config[MessageBrokerAwsConstants::SQS_AWS_CREATOR_QUEUE_NAMES] = [
    'app_messages.fifo',
    $tenantMessagesForStoreReferenceQueueDE,
    $tenantMessagesForStoreReferenceQueueAT,
    $tenantMessagesForStoreReferenceQueueUS,
];

// >>> SNS TOPICS
$config[MessageBrokerAwsConstants::SNS_AWS_CREATOR_TOPIC_NAMES] = [
    'app-store-sender-topic.fifo',
    'tenant-sender-topic.fifo',
    'message-broker.fifo',
];

$config[MessageBrokerAwsConstants::SQS_AWS_SECRET_ACCESS] = getenv('AWS_SECRET_ACCESS_KEY');
$config[MessageBrokerAwsConstants::SQS_AWS_ACCESS_KEY] = getenv('AWS_ACCESS_KEY_ID');
$config[MessageBrokerAwsConstants::SQS_AWS_ENDPOINT] = getenv('AWS_ENDPOINT');
$config[MessageBrokerAwsConstants::SQS_AWS_REGION] = getenv('AWS_DEFAULT_REGION');

$appStoreSenderTopicArn = sprintf(
    'arn:aws:sns:%s:%s:%s',
    getenv('AWS_DEFAULT_REGION'),
    getenv('AWS_ACCOUNT_ID'),
    'app-store-sender-topic.fifo',
);

$tenantSenderTopicArn = sprintf(
    'arn:aws:sns:%s:%s:%s',
    getenv('AWS_DEFAULT_REGION'),
    getenv('AWS_ACCOUNT_ID'),
    'tenant-sender-topic.fifo',
);

// >>> SNS SUBSCRIPTIONS
$config[MessageBrokerAwsConstants::SQS_AWS_TO_SNS_SUBSCRIPTIONS] = [
    [
        'TopicArn' => $appStoreSenderTopicArn,
        'Endpoint' => sprintf(
            '%s/%s/%s',
            getenv('AWS_ENDPOINT'),
            getenv('AWS_ACCOUNT_ID'),
            $tenantMessagesForStoreReferenceQueueDE,
        ),
        'FilterPolicy' => sprintf('{"storeReference":["%s"]}', $storeNameReferenceMap['DE']),
    ],
    [
        'TopicArn' => $appStoreSenderTopicArn,
        'Endpoint' => sprintf(
            '%s/%s/%s',
            getenv('AWS_ENDPOINT'),
            getenv('AWS_ACCOUNT_ID'),
            $tenantMessagesForStoreReferenceQueueAT,
        ),
        'FilterPolicy' => sprintf('{"storeReference":["%s"]}', $storeNameReferenceMap['AT']),
    ],
    [
        'TopicArn' => $appStoreSenderTopicArn,
        'Endpoint' => sprintf(
            '%s/%s/%s',
            getenv('AWS_ENDPOINT'),
            getenv('AWS_ACCOUNT_ID'),
            $tenantMessagesForStoreReferenceQueueUS,
        ),
        'FilterPolicy' => sprintf('{"storeReference":["%s"]}', $storeNameReferenceMap['US']),
    ],
    [
        'TopicArn' => $tenantSenderTopicArn,
        'Endpoint' => sprintf(
            '%s/%s/%s',
            getenv('AWS_ENDPOINT'),
            getenv('AWS_ACCOUNT_ID'),
            'app_messages.fifo',
        ),
    ],
];

$config[MessageBrokerConstants::MESSAGE_TO_CHANNEL_MAP] = [
    PaymentMethodTransfer::class => 'payment',
    PaymentMethodAddedTransfer::class => 'payment',
    PaymentCancelReservationRequestedTransfer::class => 'payment',
    PaymentConfirmationRequestedTransfer::class => 'payment',
    PaymentRefundRequestedTransfer::class => 'payment',
    PaymentMethodDeletedTransfer::class => 'payment',
    PaymentPreauthorizedTransfer::class => 'payment',
    PaymentPreauthorizationFailedTransfer::class => 'payment',
    PaymentConfirmedTransfer::class => 'payment',
    PaymentConfirmationFailedTransfer::class => 'payment',
    PaymentRefundedTransfer::class => 'payment',
    PaymentRefundFailedTransfer::class => 'payment',
    PaymentReservationCanceledTransfer::class => 'payment',
    PaymentCancelReservationFailedTransfer::class => 'payment',
    AssetAddedTransfer::class => 'assets',
    AssetUpdatedTransfer::class => 'assets',
    AssetDeletedTransfer::class => 'assets',
];

$config[MessageBrokerAwsConstants::CHANNEL_TO_SENDER_TRANSPORT_MAP] = [
    'payment' => MessageBrokerAwsConfig::SNS_TRANSPORT,
    'assets' => MessageBrokerAwsConfig::SNS_TRANSPORT,
];

$config[MessageBrokerConstants::CHANNEL_TO_TRANSPORT_MAP] =
$config[MessageBrokerAwsConstants::CHANNEL_TO_RECEIVER_TRANSPORT_MAP]
    = [
    'payment' => MessageBrokerAwsConfig::SQS_TRANSPORT,
    'assets' => MessageBrokerAwsConfig::SQS_TRANSPORT,
];

$config[MessageBrokerAwsConstants::SQS_RECEIVER_CONFIG] = json_encode([
    'default' => [
        'endpoint' => getenv('AWS_ENDPOINT'),
        'accessKeyId' => getenv('AWS_ACCESS_KEY_ID'),
        'accessKeySecret' => getenv('AWS_SECRET_ACCESS_KEY'),
        'region' => getenv('AWS_DEFAULT_REGION'),
        'poll_timeout' => 5,
        'auto_setup' => false,
    ],
    'DE' => [
        'queue_name' => $tenantMessagesForStoreReferenceQueueDE,
    ],
    'AT' => [
        'queue_name' => $tenantMessagesForStoreReferenceQueueAT,
    ],
    'US' => [
        'queue_name' => $tenantMessagesForStoreReferenceQueueUS,
    ],
]);

$config[MessageBrokerAwsConstants::SNS_SENDER_CONFIG] = [
    'endpoint' => getenv('AWS_ENDPOINT'),
    'accessKeyId' => getenv('AWS_ACCESS_KEY_ID'),
    'accessKeySecret' => getenv('AWS_SECRET_ACCESS_KEY'),
    'region' => getenv('AWS_DEFAULT_REGION'),
    'topic' => 'arn:aws:sns:eu-central-1:000000000000:tenant-sender-topic.fifo',
];

$config[MessageBrokerConstants::LOGGING_ENABLED] = true;
