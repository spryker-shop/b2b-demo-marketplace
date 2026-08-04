<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PunchoutGateway\Communication\Console;

use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutCxmlConfigurationTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultCxmlProcessorPlugin;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \Pyz\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory getBusinessFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutDemoConnectionCreateConsole extends Console
{
    protected const string COMMAND_NAME = 'punchout-gateway:demo-connection:create';

    protected const string DESCRIPTION = 'Creates demo cXML and OCI punchout connection entries (store DE).';

    protected const string STORE_NAME = 'DE';

    protected const string CXML_CONNECTION_NAME = 'Demo cXML Connection';

    protected const string CXML_SENDER_IDENTITY = 'MyNewIdentity';

    protected const string CXML_SENDER_SHARED_SECRET = 'jd8je3$ndP';

    protected const string CXML_MAPPING_EXTRINSIC_COLOR = PunchoutGatewayConfig::EXTRINSIC_PREFIX . 'ProductColor';

    protected const string OCI_CONNECTION_NAME = 'Demo OCI Connection';

    protected const string OCI_REQUEST_URL = '/punchout-gateway/oci/demo';

    protected const string OCI_MAPPING_CUSTOM_FIELD_COLOR = 'NEW_ITEM-CUST_FIELD1';

    protected const string OCI_CREDENTIALS_USERNAME = 'sonia';

    protected const string OCI_CREDENTIALS_PASSWORD = 'change123';

    protected const string OCI_CUSTOMER_EMAIL = 'sonia@acme.com';

    protected const string MAPPING_TARGET_COLOR = 'item.concreteAttributes.color';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)->setDescription(static::DESCRIPTION);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        $punchoutConnectionCollectionTransfer = (new PunchoutConnectionCollectionTransfer())
            ->addPunchoutConnection($this->createCxmlConnectionTransfer())
            ->addPunchoutConnection($this->createOciConnectionTransfer());

        $punchoutConnectionCollectionTransfer = $this->getBusinessFactory()
            ->createPunchoutDemoConnectionCreator()
            ->createDemoPunchoutConnections($punchoutConnectionCollectionTransfer);

        foreach ($punchoutConnectionCollectionTransfer->getPunchoutConnections() as $punchoutConnectionTransfer) {
            $output->writeln(sprintf(
                'Created demo punchout connection "%s" (id=%d).',
                $punchoutConnectionTransfer->getNameOrFail(),
                $punchoutConnectionTransfer->getIdPunchoutConnectionOrFail(),
            ));
        }

        return static::CODE_SUCCESS;
    }

    protected function createCxmlConnectionTransfer(): PunchoutConnectionTransfer
    {
        $punchoutCxmlConfigurationTransfer = (new PunchoutCxmlConfigurationTransfer())
            ->setSenderIdentity(static::CXML_SENDER_IDENTITY)
            ->setSenderSharedSecret(static::CXML_SENDER_SHARED_SECRET);

        return (new PunchoutConnectionTransfer())
            ->setStoreName(static::STORE_NAME)
            ->setName(static::CXML_CONNECTION_NAME)
            ->setIsActive(true)
            ->setAllowIframe(true)
            ->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_CXML)
            ->setCxmlConfiguration($punchoutCxmlConfigurationTransfer)
            ->setMappings([static::CXML_MAPPING_EXTRINSIC_COLOR => static::MAPPING_TARGET_COLOR])
            ->setProcessorPluginClass(DefaultCxmlProcessorPlugin::class);
    }

    protected function createOciConnectionTransfer(): PunchoutConnectionTransfer
    {
        $punchoutCredentialTransfer = (new PunchoutCredentialTransfer())
            ->setUsername(static::OCI_CREDENTIALS_USERNAME)
            ->setPassword(static::OCI_CREDENTIALS_PASSWORD)
            ->setCustomerEmail(static::OCI_CUSTOMER_EMAIL)
            ->setIsActive(true);

        return (new PunchoutConnectionTransfer())
            ->setStoreName(static::STORE_NAME)
            ->setName(static::OCI_CONNECTION_NAME)
            ->setIsActive(true)
            ->setAllowIframe(true)
            ->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_OCI)
            ->setRequestUrl(static::OCI_REQUEST_URL)
            ->setMappings([static::OCI_MAPPING_CUSTOM_FIELD_COLOR => static::MAPPING_TARGET_COLOR])
            ->setProcessorPluginClass(DefaultOciProcessorPlugin::class)
            ->setCredential($punchoutCredentialTransfer);
    }
}
