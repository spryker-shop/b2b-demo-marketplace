<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AuthorizationCode;

use DateInterval;
use DateTimeImmutable;
use Demo\Zed\McpCommerce\Business\Generator\OpaqueIdentifierGeneratorInterface;
use Demo\Zed\McpCommerce\McpCommerceConfig;
use Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;

class McpAuthorizationCodeWriter implements McpAuthorizationCodeWriterInterface
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
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function issue(McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer): McpAuthorizationCodeTransfer
    {
        $newMcpAuthorizationCodeTransfer = (new McpAuthorizationCodeTransfer())
            ->fromArray($mcpAuthorizationCodeTransfer->toArray(), true)
            ->setCode($this->opaqueIdentifierGenerator->generate())
            ->setExpiresAt($this->createExpiresAt())
            ->setIsUsed(false);

        if ($newMcpAuthorizationCodeTransfer->getScopes() === null) {
            $newMcpAuthorizationCodeTransfer->setScopes($this->mcpCommerceConfig->getDefaultScopes());
        }

        return $this->mcpCommerceEntityManager->createMcpAuthorizationCode($newMcpAuthorizationCodeTransfer);
    }

    /**
     * @return string
     */
    protected function createExpiresAt(): string
    {
        $ttlSeconds = $this->mcpCommerceConfig->getAuthorizationCodeTtlSeconds();

        return (new DateTimeImmutable())
            ->add(new DateInterval(sprintf('PT%dS', $ttlSeconds)))
            ->format(static::DATE_TIME_FORMAT);
    }
}
