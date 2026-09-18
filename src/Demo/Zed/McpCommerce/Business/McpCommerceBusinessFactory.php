<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business;

use Demo\Zed\McpCommerce\Business\AccessToken\McpAccessTokenValidator;
use Demo\Zed\McpCommerce\Business\AccessToken\McpAccessTokenValidatorInterface;
use Demo\Zed\McpCommerce\Business\AccessToken\McpAccessTokenWriter;
use Demo\Zed\McpCommerce\Business\AccessToken\McpAccessTokenWriterInterface;
use Demo\Zed\McpCommerce\Business\AuthorizationCode\McpAuthorizationCodeCleaner;
use Demo\Zed\McpCommerce\Business\AuthorizationCode\McpAuthorizationCodeCleanerInterface;
use Demo\Zed\McpCommerce\Business\AuthorizationCode\McpAuthorizationCodeRedeemer;
use Demo\Zed\McpCommerce\Business\AuthorizationCode\McpAuthorizationCodeRedeemerInterface;
use Demo\Zed\McpCommerce\Business\AuthorizationCode\McpAuthorizationCodeWriter;
use Demo\Zed\McpCommerce\Business\AuthorizationCode\McpAuthorizationCodeWriterInterface;
use Demo\Zed\McpCommerce\Business\Client\McpClientRegistrar;
use Demo\Zed\McpCommerce\Business\Client\McpClientRegistrarInterface;
use Demo\Zed\McpCommerce\Business\Generator\OpaqueIdentifierGenerator;
use Demo\Zed\McpCommerce\Business\Generator\OpaqueIdentifierGeneratorInterface;
use Demo\Zed\McpCommerce\Business\Pkce\PkceVerifier;
use Demo\Zed\McpCommerce\Business\Pkce\PkceVerifierInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface getRepository()
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\McpCommerce\McpCommerceConfig getConfig()
 */
class McpCommerceBusinessFactory extends AbstractBusinessFactory
{
    public function createMcpAuthorizationCodeWriter(): McpAuthorizationCodeWriterInterface
    {
        return new McpAuthorizationCodeWriter(
            $this->getEntityManager(),
            $this->createOpaqueIdentifierGenerator(),
            $this->getConfig(),
        );
    }

    public function createMcpAuthorizationCodeRedeemer(): McpAuthorizationCodeRedeemerInterface
    {
        return new McpAuthorizationCodeRedeemer(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createPkceVerifier(),
        );
    }

    public function createMcpAuthorizationCodeCleaner(): McpAuthorizationCodeCleanerInterface
    {
        return new McpAuthorizationCodeCleaner($this->getEntityManager());
    }

    public function createMcpAccessTokenWriter(): McpAccessTokenWriterInterface
    {
        return new McpAccessTokenWriter(
            $this->getEntityManager(),
            $this->createOpaqueIdentifierGenerator(),
            $this->getConfig(),
        );
    }

    public function createMcpAccessTokenValidator(): McpAccessTokenValidatorInterface
    {
        return new McpAccessTokenValidator($this->getRepository());
    }

    public function createPkceVerifier(): PkceVerifierInterface
    {
        return new PkceVerifier($this->getConfig());
    }

    public function createOpaqueIdentifierGenerator(): OpaqueIdentifierGeneratorInterface
    {
        return new OpaqueIdentifierGenerator($this->getConfig());
    }

    public function createMcpClientRegistrar(): McpClientRegistrarInterface
    {
        return new McpClientRegistrar(
            $this->getEntityManager(),
            $this->createOpaqueIdentifierGenerator(),
            $this->getConfig(),
        );
    }
}
