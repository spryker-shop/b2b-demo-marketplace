<?php

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Spryker\Zed\Event\Dependency\Plugin\EventBulkHandlerInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Component\Process\Process;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantImportListener extends AbstractPlugin implements EventBulkHandlerInterface
{
    protected const DATA_IMPORT_CONFIG_PATH = 'data/import/tenant/tenant_import_config_EU.yml';
    protected const DATA_IMPORT_FULL_CONFIG_PATH = 'data/import/local/full_EU.yml';
    protected const COMMAND_TIMEOUT = 300; // 5 minutes

    /**
     * @param array<\Generated\Shared\Transfer\QueueSendMessageTransfer> $eventEntityTransfers
     * @param string $eventName
     *
     * @return void
     */
    public function handleBulk(array $eventEntityTransfers, $eventName)
    {
        foreach ($eventEntityTransfers as $eventEntityTransfer) {
            $tenantOnboardingMessageTransfer = new TenantOnboardingMessageTransfer();
            $tenantOnboardingMessageTransfer->fromArray(json_decode($eventEntityTransfer->getBody(), true), true);

            $this->execute($tenantOnboardingMessageTransfer->getTenantRegistration());
        }
    }

    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): void
    {
        $tenantIdentifier = $tenantRegistrationTransfer->getTenantName();

        $commands = [
            $this->buildSetupCommand(),
            $this->buildStoreDataImportCommand($tenantRegistrationTransfer),
            $this->buildSetupESCommand(),
            $this->buildDataImportCommand($tenantRegistrationTransfer),
        ];

        foreach ($commands as $command) {
            $process = new Process($command);
            $process->setTimeout(static::COMMAND_TIMEOUT);

            $env = $process->getEnv();
            $env['SPRYKER_TENANT_IDENTIFIER'] = $tenantIdentifier;
            $process->setEnv($env);

            $process->mustRun();
        }
    }

    /**
     * @return array<string>
     */
    protected function buildDataImportCommand(TenantRegistrationTransfer $tenantRegistrationTransfer): array
    {
        if ($tenantRegistrationTransfer->getDataSetOrFail() === 'full') {
            return [
                'vendor/bin/console',
                'data:import',
                '--config=' . static::DATA_IMPORT_FULL_CONFIG_PATH,
            ];
        }

        return [
            'vendor/bin/console',
            'data:import',
            '--config=' . static::DATA_IMPORT_CONFIG_PATH,
        ];
    }

    /**
     * @return array<string>
     */
    protected function buildStoreDataImportCommand(TenantRegistrationTransfer $tenantRegistrationTransfer): array
    {
        if ($tenantRegistrationTransfer->getDataSetOrFail() === 'full') {
            return [
                'vendor/bin/console',
                'data:import',
                'store',
                '--config=' . static::DATA_IMPORT_FULL_CONFIG_PATH,
            ];
        }

        return [
            'vendor/bin/console',
            'data:import',
            'store',
            '--config=' . static::DATA_IMPORT_CONFIG_PATH,
        ];
    }

    /**
     * @return array<string>
     */
    protected function buildSetupCommand(): array
    {
        return [
            'vendor/bin/console',
            'setup:init-db',
        ];
    }

    /**
     * @return array<string>
     */
    protected function buildSetupESCommand(): array
    {
        return [
            'vendor/bin/console',
            'search:setup:sources',
        ];
    }
}
