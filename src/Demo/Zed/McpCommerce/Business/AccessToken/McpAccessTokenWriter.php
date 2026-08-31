<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AccessToken;

use DateInterval;
use DateTimeImmutable;
use Demo\Zed\McpCommerce\Business\Generator\OpaqueIdentifierGeneratorInterface;
use Demo\Zed\McpCommerce\McpCommerceConfig;
use Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface;
use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;

class McpAccessTokenWriter implements McpAccessTokenWriterInterface
{
    /**
     * @var string
     */
    protected const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface
     */
    protected McpCommerceEntityManagerInterface $mcpCommerceEntityManager;

    /**
     * @var \Demo\Zed\McpCommerce\Business\Generator\OpaqueIdentifierGeneratorInterface
     */
    protected OpaqueIdentifierGeneratorInterface $opaqueIdentifierGenerator;

    /**
     * @var \Demo\Zed\McpCommerce\McpCommerceConfig
     */
    protected McpCommerceConfig $mcpCommerceConfig;

    /**
     * @param \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface $mcpCommerceEntityManager
     * @param \Demo\Zed\McpCommerce\Business\Generator\OpaqueIdentifierGeneratorInterface $opaqueIdentifierGenerator
     * @param \Demo\Zed\McpCommerce\McpCommerceConfig $mcpCommerceConfig
     */
    public function __construct(
        McpCommerceEntityManagerInterface $mcpCommerceEntityManager,
        OpaqueIdentifierGeneratorInterface $opaqueIdentifierGenerator,
        McpCommerceConfig $mcpCommerceConfig,
    ) {
        $this->mcpCommerceEntityManager = $mcpCommerceEntityManager;
        $this->opaqueIdentifierGenerator = $opaqueIdentifierGenerator;
        $this->mcpCommerceConfig = $mcpCommerceConfig;
    }

    /**
     * @param \Generated\Shared\Transfer\McpIdentityTransfer $mcpIdentityTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function issue(McpIdentityTransfer $mcpIdentityTransfer): McpAccessTokenTransfer
    {
        $ttlSeconds = $this->mcpCommerceConfig->getAccessTokenTtlSeconds();
        $scopes = $mcpIdentityTransfer->getScopes() ?? $this->mcpCommerceConfig->getDefaultScopes();

        $mcpAccessTokenTransfer = (new McpAccessTokenTransfer())
            ->setIdentifier($this->opaqueIdentifierGenerator->generate())
            ->setClientIdentifier($mcpIdentityTransfer->getClientIdentifierOrFail())
            ->setCustomerReference($mcpIdentityTransfer->getCustomerReferenceOrFail())
            ->setIdCustomer($mcpIdentityTransfer->getIdCustomerOrFail())
            ->setScopes($scopes)
            ->setExpiresAt($this->createExpiresAt($ttlSeconds))
            ->setIsRevoked(false);

        return $this->mcpCommerceEntityManager
            ->createMcpAccessToken($mcpAccessTokenTransfer)
            ->setExpiresIn($ttlSeconds);
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function revoke(string $identifier): bool
    {
        if ($identifier === '') {
            return false;
        }

        return $this->mcpCommerceEntityManager->revokeMcpAccessToken($identifier) > 0;
    }

    /**
     * @param int $ttlSeconds
     *
     * @return string
     */
    protected function createExpiresAt(int $ttlSeconds): string
    {
        return (new DateTimeImmutable())
            ->add(new DateInterval(sprintf('PT%dS', $ttlSeconds)))
            ->format(static::DATE_TIME_FORMAT);
    }
}
