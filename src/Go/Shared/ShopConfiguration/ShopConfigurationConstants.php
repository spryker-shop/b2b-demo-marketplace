<?php

declare(strict_types=1);

namespace Go\Shared\ShopConfiguration;

interface ShopConfigurationConstants
{
    /**
     * Resource name for ACL
     */
    public const RESOURCE_NAME = 'shop-configuration';

    /**
     * ACL permissions
     */
    public const PERMISSION_VIEW = 'view';
    public const PERMISSION_EDIT = 'edit';
    public const PERMISSION_PUBLISH = 'publish';

    /**
     * Data types
     */
    public const DATA_TYPE_STRING = 'string';
    public const DATA_TYPE_INT = 'int';
    public const DATA_TYPE_FLOAT = 'float';
    public const DATA_TYPE_BOOL = 'bool';
    public const DATA_TYPE_ARRAY = 'array';
    public const DATA_TYPE_JSON = 'json';
    public const DATA_TYPE_ENUM = 'enum';

    /**
     * Configuration file extensions
     */
    public const SUPPORTED_EXTENSIONS = ['yml', 'yaml', 'xml', 'json'];

    /**
     * Discovery paths
     */
    public const DISCOVERY_PATHS = [
        'src/{namespace}/Shared/{module}/ShopConfiguration/',
        'src/{namespace}/Zed/{module}/Communication/Resources/shop_configuration/',
    ];

    /**
     * Redis key patterns
     */
    public const REDIS_KEY_PATTERN_STORE = '{store}:shop_configuration';
    public const REDIS_KEY_PATTERN_STORE_LOCALE = '{store}:shop_configuration:{locale}';

    /**
     * Security constants
     */
    public const SENSITIVE_FIELDS = [
        'api_key',
        'secret',
        'password',
        'token',
        'private_key',
        'merchant_id',
        'client_secret',
    ];
}
