<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PunchoutGateway\Communication\Console;

use Orm\Zed\Customer\Persistence\SpyCustomerQuery;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnection;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnectionQuery;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutCredential;
use Spryker\Zed\Kernel\Communication\Console\Console;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultCxmlProcessorPlugin;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
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

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)->setDescription(static::DESCRIPTION);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        $storeTransfer = $this->getFactory()->getStoreFacade()->getStoreByName(static::STORE_NAME);
        $idStore = $storeTransfer->getIdStoreOrFail();

        $this->createCxmlConnection($output, $idStore);
        $this->createOciConnection($output, $idStore);

        return static::CODE_SUCCESS;
    }

    /**
     * @SuppressWarnings(OrmNewEntityNotInCommunicationRule) Legacy: one-off demo-data seeding console creates the ORM entity by design. Do not suppress for new code.
     */
    protected function createCxmlConnection(OutputInterface $output, int $idStore): void
    {
        $existingEntity = SpyPunchoutConnectionQuery::create()
            ->filterBySenderIdentity(static::CXML_SENDER_IDENTITY)
            ->findOne();

        if ($existingEntity !== null) {
            $output->writeln(sprintf('cXML connection already exists (id=%d). Skipping.', $existingEntity->getIdPunchoutConnection()));

            return;
        }

        $entity = new SpyPunchoutConnection();
        $entity->setFkStore($idStore);
        $entity->setName(static::CXML_CONNECTION_NAME);
        $entity->setIsActive(true);
        $entity->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_CXML);
        $entity->setSenderIdentity(static::CXML_SENDER_IDENTITY);
        $entity->setConfiguration((string)json_encode([
            PunchoutGatewayConfig::CONFIGURATION_KEY_SENDER_SHARED_SECRET => password_hash(static::CXML_SENDER_SHARED_SECRET, PASSWORD_DEFAULT),
            PunchoutGatewayConfig::CONFIGURATION_KEY_MAPPING => [static::CXML_MAPPING_EXTRINSIC_COLOR => 'item.concreteAttributes.color'],
        ]));
        $entity->setProcessorPluginClass(DefaultCxmlProcessorPlugin::class);
        $entity->setAllowIframe(true);
        $entity->save();

        $output->writeln(sprintf('Created cXML demo connection (id=%d).', $entity->getIdPunchoutConnection()));
    }

    /**
     * @SuppressWarnings(OrmNewEntityNotInCommunicationRule) Legacy: one-off demo-data seeding console creates the ORM entity by design. Do not suppress for new code.
     */
    protected function createOciConnection(OutputInterface $output, int $idStore): void
    {
        $existingEntity = SpyPunchoutConnectionQuery::create()
            ->filterByFkStore($idStore)
            ->filterByRequestUrl(static::OCI_REQUEST_URL)
            ->findOne();

        if ($existingEntity !== null) {
            $output->writeln(sprintf('OCI connection already exists (id=%d). Skipping.', $existingEntity->getIdPunchoutConnection()));

            return;
        }

        $punchoutConnectionEntity = new SpyPunchoutConnection();
        $punchoutConnectionEntity->setFkStore($idStore);
        $punchoutConnectionEntity->setName(static::OCI_CONNECTION_NAME);
        $punchoutConnectionEntity->setIsActive(true);
        $punchoutConnectionEntity->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_OCI);
        $punchoutConnectionEntity->setRequestUrl(static::OCI_REQUEST_URL);
        $punchoutConnectionEntity->setAllowIframe(true);
        $punchoutConnectionEntity->setConfiguration((string)json_encode([
            PunchoutGatewayConfig::CONFIGURATION_KEY_MAPPING => [static::OCI_MAPPING_CUSTOM_FIELD_COLOR => 'item.concreteAttributes.color'],
        ]));
        $punchoutConnectionEntity->setProcessorPluginClass(DefaultOciProcessorPlugin::class);
        $punchoutConnectionEntity->save();

        $output->writeln(sprintf('Created OCI demo connection (id=%d).', $punchoutConnectionEntity->getIdPunchoutConnection()));

        $credentialEntity = new SpyPunchoutCredential();
        $credentialEntity->setFkPunchoutConnection($punchoutConnectionEntity->getIdPunchoutConnection());
        $credentialEntity->setUsername(static::OCI_CREDENTIALS_USERNAME);
        $credentialEntity->setPasswordHash(password_hash(static::OCI_CREDENTIALS_PASSWORD, PASSWORD_DEFAULT));

        $customer = SpyCustomerQuery::create()->filterByEmail(static::OCI_CUSTOMER_EMAIL)->findOne();

        if ($customer === null) {
            $output->writeln(sprintf('Customer with email %s for OCI connection was not found. Please create credentials manually.', static::OCI_CUSTOMER_EMAIL));

            return;
        }

        $credentialEntity->setFkCustomer($customer->getIdCustomer());
        $credentialEntity->save();

        $output->writeln(sprintf('Created OCI credentials (id=%d).', $credentialEntity->getIdPunchoutCredential()));
    }
}
