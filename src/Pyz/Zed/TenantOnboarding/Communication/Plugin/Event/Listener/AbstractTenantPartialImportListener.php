<?php

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Component\Process\Process;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
abstract class AbstractTenantPartialImportListener extends AbstractPlugin implements EventHandlerInterface
{
    protected const DATA_IMPORT_FULL_CONFIG_PATH = '';
    protected const COMMAND_TIMEOUT = 540; // 9 minutes

    public function handle(TransferInterface $transfer, $eventName)
    {
        /** @var \Generated\Shared\Transfer\QueueSendMessageTransfer $transfer */
        $tenantOnboardingMessageTransfer = new TenantOnboardingMessageTransfer();
        $tenantOnboardingMessageTransfer->fromArray(json_decode($transfer->getBody(), true), true);

        $this->execute($tenantOnboardingMessageTransfer->getTenantRegistration());
    }

    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): void
    {
        if ($tenantRegistrationTransfer->getDataSetOrFail() !== 'full') {
            return;
        }
        $tenantIdentifier = $tenantRegistrationTransfer->getTenantName();

        $commands = [
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
        return [
            'vendor/bin/console',
            'data:import',
            '--config=' . static::DATA_IMPORT_FULL_CONFIG_PATH,
        ];
    }
}
