<?php

namespace Go\Zed\TenantOnboarding\Communication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TenantOnboardingTestRunConsole extends \Spryker\Zed\Kernel\Communication\Console\Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'tenant:test';

    /**
     * @var string
     */
    public const DESCRIPTION = 'Console command for testing tenant onboarding';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::DESCRIPTION);

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new \Spryker\Zed\RabbitMq\Business\RabbitMqFacade())->purgeAllQueues(
            $this->getMessenger(),
        );
        $dataArray = [
            json_decode('{
                          "id_tenant_registration": 1,
                          "attempt": null,
                          "max_attempts": null,
                          "tenant_registration": {
                            "id_tenant_registration": 1,
                            "company_name": "Spryker",
                            "tenant_name": "spryker",
                            "email": "dmitriy.krainiy@spryker.com",
                            "password": null,
                            "password_hash": "$2y$10$oLZdG6DkdN2HcD\/xCGQEz.UGj9xwllghwpEtpLDX2YQhthMaOYaky",
                            "data_set": "demo",
                            "status": "approved",
                            "decline_reason": null,
                            "created_at": "2025-08-28 08:20:35.329398",
                            "updated_at": "2025-08-28 08:48:10",
                            "errors": null,
                            "backoffice_host": null,
                            "tenant": null
                          }
                        }', true),
            json_decode('{
                          "id_tenant_registration": 2,
                          "attempt": null,
                          "max_attempts": null,
                          "tenant_registration": {
                            "id_tenant_registration": 2,
                            "company_name": "Test",
                            "tenant_name": "test",
                            "email": "dmitriy.krainiy+1@spryker.com",
                            "password": null,
                            "password_hash": "$2y$10$oLZdG6DkdN2HcD\/xCGQEz.UGj9xwllghwpEtpLDX2YQhthMaOYaky",
                            "data_set": "full",
                            "status": "approved",
                            "decline_reason": null,
                            "created_at": "2025-08-28 08:20:35.329398",
                            "updated_at": "2025-08-28 08:48:10",
                            "errors": null,
                            "backoffice_host": null,
                            "tenant": null
                          }
                        }', true),
            json_decode('{
                          "id_tenant_registration": 3,
                          "attempt": null,
                          "max_attempts": null,
                          "tenant_registration": {
                            "id_tenant_registration": 3,
                            "company_name": "Spryker 2",
                            "tenant_name": "spryker_2",
                            "email": "dmitriy.krainiy+3@spryker.com",
                            "password": null,
                            "password_hash": "$2y$10$oLZdG6DkdN2HcD\/xCGQEz.UGj9xwllghwpEtpLDX2YQhthMaOYaky",
                            "data_set": "demo",
                            "status": "approved",
                            "decline_reason": null,
                            "created_at": "2025-08-28 08:20:35.329398",
                            "updated_at": "2025-08-28 08:48:10",
                            "errors": null,
                            "backoffice_host": null,
                            "tenant": null
                          }
                        }', true),
        ];
        foreach ($dataArray as $data) {
            $dataSave = $data['tenant_registration'];
            unset($dataSave['id_tenant_registration']);
            if (!(\Orm\Zed\TenantOnboarding\Persistence\PyzTenantRegistrationQuery::create()->filterByPrimaryKey($data['id_tenant_registration'])->findOne())) {
                (new \Orm\Zed\TenantOnboarding\Persistence\PyzTenantRegistration())
                    ->fromArray($dataSave)
                    ->save();
            }

            $eventName = \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT;
            if ($dataSave['data_set'] === 'full') {
                $eventName = \Go\Zed\TenantOnboarding\TenantOnboardingConfig::TENANT_REGISTERED_EVENT_WITH_FULL_DATA;
            }
            (new \Spryker\Zed\Event\Business\EventFacade())->trigger(
                $eventName,
                (new \Generated\Shared\Transfer\QueueSendMessageTransfer())->setBody(json_encode($data)),
            );
        }
        return static::CODE_SUCCESS;
    }
}
