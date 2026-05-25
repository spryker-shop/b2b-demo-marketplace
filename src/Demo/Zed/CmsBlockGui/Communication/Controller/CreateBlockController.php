<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockGui\Communication\Controller;

use Generated\Shared\Transfer\CmsBlockTransfer;
use Spryker\Zed\CmsBlock\Business\Exception\CmsBlockTemplateNotFoundException;
use Spryker\Zed\CmsBlockGui\Communication\Controller\CreateBlockController as SprykerCreateBlockController;
use Spryker\Zed\CmsBlockGui\Communication\Form\Block\CmsBlockForm;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Demo\Zed\CmsBlockGui\Communication\CmsBlockGuiCommunicationFactory getFactory()
 */
class CreateBlockController extends SprykerCreateBlockController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    public function indexAction(Request $request): RedirectResponse|array
    {
        $this->getFactory()
            ->getCmsBlockFacade()
            ->syncTemplate($this->getFactory()->getConfig()->getTemplatePath());

        $availableLocales = $this->getFactory()->getLocaleFacade()->getLocaleCollection();
        $cmsBlockFormDataProvider = $this->getFactory()->createCmsBlockFormDataProvider();
        $cmsBlockForm = $this->getFactory()
            ->createCmsBlockForm($cmsBlockFormDataProvider)
            ->handleRequest($request);

        if ($cmsBlockForm->isSubmitted() && $cmsBlockForm->isValid()) {
            $cmsBlockTransfer = $this->createBlock($cmsBlockForm);
            if ($cmsBlockTransfer) {
                return $this->redirectResponse($this->createSuccessRedirectUrl($cmsBlockTransfer));
            }
        }

        if ($cmsBlockForm->isSubmitted() && !$cmsBlockForm->isValid()) {
            $this->addErrorMessage(static::ERROR_MESSAGE_INVALID_DATA_PROVIDED);
        }

        return $this->viewResponse([
            'cmsBlockForm' => $cmsBlockForm->createView(),
            'cmsBlockTabs' => $this->getFactory()->createCmsBlockFormAddTabs()->createView(),
            'availableLocales' => $availableLocales,
        ]);
    }

    protected function createBlock(FormInterface $cmsBlockForm): ?CmsBlockTransfer
    {
        try {
            /** @var \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer */
            $cmsBlockTransfer = $this->getFactory()
                ->getCmsBlockFacade()
                ->createCmsBlock($cmsBlockForm->getData());

            $this->getFactory()
                ->getCmsBlockCustomerGroupFacade()
                ->saveCmsBlockCustomerGroups($cmsBlockTransfer);

            $this->addSuccessMessage(static::MESSAGE_SUCCESSFUL_CMS_BLOCK_CREATED);
        } catch (CmsBlockTemplateNotFoundException) {
            $this->addErrorMessage(static::ERROR_MESSAGE_INVALID_DATA_PROVIDED);
            $cmsBlockForm->get(CmsBlockForm::FIELD_FK_TEMPLATE)
                ->addError(new FormError(static::ERROR_MESSAGE_LOST_TEMPLATE));

            return null;
        }

        return $cmsBlockTransfer;
    }
}
