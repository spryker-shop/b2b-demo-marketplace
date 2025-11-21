<?php

declare(strict_types = 1);

use Spryker\Service\FlysystemAws3v3FileSystem\Plugin\Flysystem\Aws3v3FilesystemBuilderPlugin;
use Spryker\Shared\FileSystem\FileSystemConstants;

require 'common/config_oms-development.php';

// >>> FILESYSTEM
$config[FileSystemConstants::FILESYSTEM_SERVICE] = [
    'ssp-inquiry' => [
        'sprykerAdapterClass' => Aws3v3FilesystemBuilderPlugin::class,
        'key' => getenv('SPRYKER_S3_SSP_CLAIM_KEY') ?: '',
        'secret' => getenv('SPRYKER_S3_SSP_CLAIM_SECRET') ?: '',
        'bucket' => getenv('SPRYKER_S3_SSP_CLAIM_BUCKET') ?: '',
        'region' => getenv('AWS_REGION') ?: 'eu-central-1',
        'version' => 'latest',
        'root' => '/ssp-inquiry',
        'path' => '',
    ],
    'files' => [
        'sprykerAdapterClass' => Aws3v3FilesystemBuilderPlugin::class,
        'key' => getenv('SPRYKER_S3_SSP_FILES_KEY') ?: '',
        'secret' => getenv('SPRYKER_S3_SSP_FILES_SECRET') ?: '',
        'bucket' => getenv('SPRYKER_S3_SSP_FILES_BUCKET') ?: '',
        'region' => getenv('AWS_REGION') ?: 'eu-central-1',
        'version' => 'latest',
        'root' => '/files',
        'path' => '',
    ],
    'ssp-asset-image' => [
        'sprykerAdapterClass' => Aws3v3FilesystemBuilderPlugin::class,
        'key' => getenv('SPRYKER_S3_SSP_ASSETS_KEY') ?: '',
        'secret' => getenv('SPRYKER_S3_SSP_ASSETS_SECRET') ?: '',
        'bucket' => getenv('SPRYKER_S3_SSP_ASSETS_BUCKET') ?: '',
        'region' => getenv('AWS_REGION') ?: 'eu-central-1',
        'version' => 'latest',
        'root' => '/ssp-asset-image',
        'path' => '',
    ],
    'ssp-model-image' => [
        'sprykerAdapterClass' => Aws3v3FilesystemBuilderPlugin::class,
        'key' => getenv('SPRYKER_S3_SSP_MODELS_KEY') ?: '',
        'secret' => getenv('SPRYKER_S3_SSP_MODELS_SECRET') ?: '',
        'bucket' => getenv('SPRYKER_S3_SSP_MODELS_BUCKET') ?: '',
        'region' => getenv('AWS_REGION') ?: 'eu-central-1',
        'version' => 'latest',
        'root' => '/ssp-model-image',
        'path' => '',
    ],
];
