<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Communication\Console;

use Demo\Shared\McpCommerce\McpCommerceConstants;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Turns the MCP Commerce Server on or off from the command line.
 *
 * The feature ships fail-closed, so a freshly provisioned environment has it disabled and every MCP
 * endpoint answers 404. Enabling it through the Back Office is the normal route; this command exists
 * for the cases where that is not available — a provisioning script, a demo runbook, or an automated
 * test arranging its own precondition.
 *
 * Values are written through the Configuration facade rather than straight into storage, so schema
 * validation runs and the post-save plugin stack publishes the value to the key-value store the Glue
 * layer reads. Writing the database alone would leave the storefront reading a stale value.
 *
 * @method \Demo\Zed\McpCommerce\Business\McpCommerceFacadeInterface getFacade()
 * @method \Demo\Zed\McpCommerce\McpCommerceConfig getConfig()
 */
class McpCommerceFeatureFlagConsole extends Console
{
    /**
     * @var string
     */
    protected const COMMAND_NAME = 'mcp-commerce:feature-flag';

    /**
     * @var string
     */
    protected const DESCRIPTION = 'Enables or disables the MCP Commerce Server (mcp_commerce:server:general:is_enabled).';

    /**
     * @var string
     */
    protected const ARGUMENT_STATE = 'state';

    /**
     * @var string
     */
    protected const ARGUMENT_STATE_DESCRIPTION = 'Either "enable" or "disable".';

    /**
     * @var string
     */
    protected const STATE_ENABLE = 'enable';

    /**
     * @var string
     */
    protected const STATE_DISABLE = 'disable';

    /**
     * @var string
     */
    protected const SCOPE_GLOBAL = 'global';

    /**
     * @var string
     */
    protected const VALUE_TRUE = 'true';

    /**
     * @var string
     */
    protected const VALUE_FALSE = 'false';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::DESCRIPTION);
        $this->addArgument(
            static::ARGUMENT_STATE,
            InputArgument::REQUIRED,
            static::ARGUMENT_STATE_DESCRIPTION,
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $state = (string)$input->getArgument(static::ARGUMENT_STATE);

        if (!in_array($state, [static::STATE_ENABLE, static::STATE_DISABLE], true)) {
            $output->writeln(sprintf(
                '<error>Unknown state "%s". Use "%s" or "%s".</error>',
                $state,
                static::STATE_ENABLE,
                static::STATE_DISABLE,
            ));

            return static::CODE_ERROR;
        }

        $value = $state === static::STATE_ENABLE ? static::VALUE_TRUE : static::VALUE_FALSE;

        $configurationValueCollectionResponseTransfer = $this->getFactory()
            ->getConfigurationFacade()
            ->saveConfigurationValues(
                (new ConfigurationValueCollectionRequestTransfer())
                    ->addConfigurationValue(
                        (new ConfigurationValueTransfer())
                            ->setSettingKey(McpCommerceConstants::CONFIGURATION_KEY_IS_ENABLED)
                            ->setScope(static::SCOPE_GLOBAL)
                            ->setValue($value),
                    ),
            );

        if ($configurationValueCollectionResponseTransfer->getIsSuccess() !== true) {
            $output->writeln(sprintf(
                '<error>Could not %s the MCP Commerce Server: %s</error>',
                $state,
                $this->formatErrors($configurationValueCollectionResponseTransfer->getErrors()->getArrayCopy()),
            ));

            return static::CODE_ERROR;
        }

        $output->writeln(sprintf(
            '<info>MCP Commerce Server %sd (%s = %s).</info>',
            $state,
            McpCommerceConstants::CONFIGURATION_KEY_IS_ENABLED,
            $value,
        ));

        return static::CODE_SUCCESS;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ConfigurationErrorTransfer> $configurationErrorTransfers
     */
    protected function formatErrors(array $configurationErrorTransfers): string
    {
        $messages = [];

        foreach ($configurationErrorTransfers as $configurationErrorTransfer) {
            $messages[] = (string)$configurationErrorTransfer->getMessage();
        }

        return $messages === [] ? 'no error detail was returned' : implode('; ', $messages);
    }
}
