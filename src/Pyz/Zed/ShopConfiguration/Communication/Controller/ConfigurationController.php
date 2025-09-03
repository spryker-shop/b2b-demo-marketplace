<?php

namespace Pyz\Zed\ShopConfiguration\Communication\Controller;

use Generated\Shared\Transfer\FileSystemStreamTransfer;
use Orm\Zed\TenantOnboarding\Persistence\SpyStoreConfigQuery;
use Orm\Zed\TenantOnboarding\Persistence\SpyStoreDomainQuery;
use Spryker\Zed\FileManagerGui\Communication\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pyz\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Pyz\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 */
class ConfigurationController extends \Spryker\Zed\Kernel\Communication\Controller\AbstractController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function indexAction(Request $request): array|\Symfony\Component\HttpFoundation\Response
    {
        $tenantId = $this->getFactory()->getLocator()->tenantBehavior()->facade()->getCurrentTenantId();
        $currentTenant = $tenantId;
        $storeTransfers = $this->getFactory()->getLocator()->store()->facade()->getAllStores();
        if (count($storeTransfers) === 0) {
            $this->addErrorMessage('Create at least one store to use Shop configurator.');
            return $this->redirectResponse('/store-gui/list');
        }

        $storeName = $request->query->get('storeName');
        if (!$storeName) {
            /** @var \Generated\Shared\Transfer\StoreTransfer $storeTransfer */
            $storeTransfer = reset($storeTransfers);
            $storeName = $storeTransfer->getName();
        } else {
            $storeTransfer = $this->getFactory()->getLocator()->store()->facade()->getStoreByName($storeName);
        }
        if (!$tenantId) {
            $tenantId = $storeTransfer->getIdTenantOrFail();
        }

        $storeConfigEntity = SpyStoreConfigQuery::create()
            ->filterByTenantIdentifier($tenantId)
            ->filterByStore($storeName)
            ->findOne();
        $data = [];
        if ($storeConfigEntity) {
            $data = $storeConfigEntity->getData();
        }

        $storeDomainEntity = SpyStoreDomainQuery::create()
            ->filterByStoreName($storeName)
            ->filterByTenantIdentifier($tenantId)
            ->findOne();
        if ($storeDomainEntity) {
            $data['shop_domain'] = str_replace('.' . $this->getFactory()->getConfig()->getStoreFrontHost(), '', $storeDomainEntity->getDomainHost());
        }

        $form = $this->getFactory()
            ->getStoreConfigurationForm($data)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['shop_domain'] = $data['shop_domain'] . '.' . $this->getFactory()->getConfig()->getStoreFrontHost();
            $logoUrl = $this->saveConfigurationFile('logo', $tenantId, $storeName, $form);
            if ($logoUrl) {
                $data['logo'] = $logoUrl;
            }
            $faviconUrl = $this->saveConfigurationFile('favicon', $tenantId, $storeName, $form);
            if ($faviconUrl) {
                $data['favicon'] = $faviconUrl;
            }

            $storeConfigEntity = SpyStoreConfigQuery::create()
                ->filterByTenantIdentifier($tenantId)
                ->filterByStore($storeName)
                ->findOneOrCreate();
            $storeConfigEntity->setData($data);
            $storeConfigEntity->save();

            if (isset($data['shop_domain'])) {
                $storeDomainEntity = SpyStoreDomainQuery::create()
                    ->filterByStoreName($storeName)
                    ->filterByTenantIdentifier($tenantId)
                    ->findOneOrCreate();
                $storeDomainEntity->setDomainHost($data['shop_domain']);
                $storeDomainEntity->setData([
                    'tenant' => $tenantId,
                    'store' => $storeName,
                ]);
                $storeDomainEntity->save();

                $storeDomainEntities = SpyStoreDomainQuery::create()
                    ->filterByTenantIdentifier($tenantId)
                    ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNOTNULL)
                    ->find();

                $storeDomainListData = [];
                foreach ($storeDomainEntities as $storeDomainEntity) {
                    $storeDomainListData[$storeDomainEntity->getStorename()] = $storeDomainEntity->getDomainHost();
                }

                $storeDomainEntity = SpyStoreDomainQuery::create()
                    ->filterByTenantIdentifier($tenantId)
                    ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNULL)
                    ->findOneOrCreate();
                $storeDomainEntity->setDomainHost($tenantId);
                $storeDomainEntity->setData($storeDomainListData);
                $storeDomainEntity->save();
            }

            if (!$storeConfigEntity->getIdStoreConfig()) {
                $this->addErrorMessage('Failed to update Store Configuration.');
            } else {
                $this->addSuccessMessage('Store Configuration updated successfully.');
            }
        }

        return $this->viewResponse([
            'form' => $form->createView(),
            'data' => $data,
            'stores' => $storeTransfers,
            'currentStore' => $storeName,
            'currentTenant' => $currentTenant,
        ]);
    }

    protected function saveConfigurationFile(string $propertyName, string $tenantId, string $storeName, \Symfony\Component\Form\FormInterface $form): ?string
    {
        /** @var UploadedFile|null $file */
        $file = $form->get($propertyName)->getData();
        $awsFileStorageBucket = $this->getFactory()->getConfig()->getAwsFileStorageBucket();
        if (!$file || !$awsFileStorageBucket) {
            return null;
        }

        $extension = $file->guessExtension() ?: 'png';
        $key = sprintf('%s/%s/%s.%s',
            $tenantId,
            $storeName,
            bin2hex(random_bytes(16)),
            $extension
        );

        $stream = fopen($file->getRealPath(), 'rb'); // short-lived tmp path
        try {
            $fileSystemStreamTransfer = (new FileSystemStreamTransfer())
                ->setPath($key)
                ->setFileSystemName('configuration')
                ->setConfig([
                    'mimetype'           => $file->getMimeType(),
                    'CacheControl'       => 'public, max-age=31536000, immutable',
                    'ContentDisposition' => 'inline',
                ]);
            $this->getFactory()
                ->getFileSystemService()
                ->writeStream($fileSystemStreamTransfer, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        // Persist a reference (public URL or storage key) on your entity/transfer:
        $publicUrl = sprintf(
            'https://%s.s3.amazonaws.com/config/%s',
            $awsFileStorageBucket,
            $key
        );

        return $publicUrl;
    }
}
