<?php

namespace Go\Zed\ShopConfiguration\Communication\Controller;

use Generated\Shared\Transfer\FileSystemStreamTransfer;
use Orm\Zed\TenantOnboarding\Persistence\SpyStoreConfigQuery;
use Orm\Zed\TenantOnboarding\Persistence\SpyStoreDomainQuery;
use Spryker\Zed\FileManagerGui\Communication\File\UploadedFile;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Go\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Go\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 */
class ConfigurationController extends \Spryker\Zed\Kernel\Communication\Controller\AbstractController
{
    public function indexAction(Request $request): array|\Symfony\Component\HttpFoundation\Response
    {
        $currentTenantReference = $this->getFactory()->getLocator()->tenantBehavior()->facade()->getCurrentTenantReference();
        $currentTenant = $currentTenantReference;
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
        if (!$currentTenantReference) {
            $currentTenantReference = $storeTransfer->getTenantReferenceOrFail();
        }

        $storeConfigEntity = SpyStoreConfigQuery::create()
            ->filterByTenantIdentifier($currentTenantReference)
            ->filterByStore($storeName)
            ->findOne();
        $data = [];
        if ($storeConfigEntity) {
            $data = $storeConfigEntity->getData();
        }

        $storeDomainEntity = SpyStoreDomainQuery::create()
            ->filterByStoreName($storeName)
            ->filterByTenantIdentifier($currentTenantReference)
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
            $logoUrl = $this->saveConfigurationFile('logo', $currentTenantReference, $storeName, $form);
            if ($logoUrl) {
                $data['logo'] = $logoUrl;
            }
            $faviconUrl = $this->saveConfigurationFile('favicon', $currentTenantReference, $storeName, $form);
            if ($faviconUrl) {
                $data['favicon'] = $faviconUrl;
            }

            $storeConfigEntity = SpyStoreConfigQuery::create()
                ->filterByTenantIdentifier($currentTenantReference)
                ->filterByStore($storeName)
                ->findOneOrCreate();
            $storeConfigEntity->setData($data);
            $storeConfigEntity->save();

            if (isset($data['shop_domain'])) {
                $storeDomainEntity = SpyStoreDomainQuery::create()
                    ->filterByStoreName($storeName)
                    ->filterByTenantIdentifier($currentTenantReference)
                    ->findOneOrCreate();
                $storeDomainEntity->setDomainHost($data['shop_domain']);
                $storeDomainEntity->setData([
                    'tenant' => $currentTenantReference,
                    'store' => $storeName,
                ]);
                $storeDomainEntity->save();

                $storeDomainEntities = SpyStoreDomainQuery::create()
                    ->filterByTenantIdentifier($currentTenantReference)
                    ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNOTNULL)
                    ->find();

                $storeDomainListData = [];
                foreach ($storeDomainEntities as $storeDomainEntity) {
                    $storeDomainListData[$storeDomainEntity->getStorename()] = $storeDomainEntity->getDomainHost();
                }

                $storeDomainEntity = SpyStoreDomainQuery::create()
                    ->filterByTenantIdentifier($currentTenantReference)
                    ->filterByStorename(null, \Propel\Runtime\ActiveQuery\Criteria::ISNULL)
                    ->findOneOrCreate();
                $storeDomainEntity->setDomainHost($currentTenantReference);
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

    protected function saveConfigurationFile(string $propertyName, string $tenantReference, string $storeName, FormInterface $form): ?string
    {
        /** @var UploadedFile|null $file */
        $file = $form->get($propertyName)->getData();
        $awsFileStorageBucket = $this->getFactory()->getConfig()->getAwsFileStorageBucket();
        if (!$file) {
            return null;
        }

        $extension = $file->guessExtension() ?: 'png';
        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $key = sprintf('%s_%s',
            sha1($tenantReference . $storeName),
            $fileName,
        );

        $stream = fopen($file->getRealPath(), 'rb');
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

        $publicUrl = sprintf(
            '/assets/static/images/%s',
            $key
        );

        // Persist a reference (public URL or storage key) on your entity/transfer:
        if ($awsFileStorageBucket) {
            $publicUrl = sprintf(
                'https://%s.s3.amazonaws.com/config/%s',
                $awsFileStorageBucket,
                $key
            );
        }

        return $publicUrl;
    }
}
