<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockGui\Communication\Controller;

use Spryker\Zed\CmsBlockGui\Communication\Controller\EditBlockController as SprykerEditBlockController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Demo\Zed\CmsBlockGui\Communication\CmsBlockGuiCommunicationFactory getFactory()
 */
class EditBlockController extends SprykerEditBlockController
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

        $idCmsBlock = $request->query->getInt(static::URL_PARAM_ID_CMS_BLOCK);
        $cmsBlockTransfer = $this->findCmsBlockById($idCmsBlock);

        if (!$cmsBlockTransfer) {
            $this->addErrorMessage(static::MESSAGE_CMS_BLOCK_INVALID_ID_ERROR);

            return $this->getNotFoundBlockRedirect();
        }

        $cmsBlockFormDataProvider = $this->getFactory()->createCmsBlockFormDataProvider();
        $cmsBlockForm = $this->getFactory()
            ->createCmsBlockForm($cmsBlockFormDataProvider, $cmsBlockTransfer->getIdCmsBlock())
            ->handleRequest($request);

        if ($cmsBlockForm->isSubmitted() && $cmsBlockForm->isValid() && $this->updateCmsBlock($cmsBlockForm)) {
            $this->saveCmsBlockCustomerGroups($cmsBlockForm);

            return $this->redirectResponse($this->createEditCmsBlockUrl($cmsBlockTransfer->getIdCmsBlockOrFail()));
        }

        return $this->viewResponse([
            'idCmsBlock' => $cmsBlockTransfer->getIdCmsBlock(),
            'cmsBlockForm' => $cmsBlockForm->createView(),
            'cmsBlockTabs' => $this->getFactory()->createCmsBlockFormEditTabs()->createView(),
            'availableLocales' => $this->getFactory()->getLocaleFacade()->getLocaleCollection(),
            'cmsBlock' => $cmsBlockTransfer,
            'toggleActiveCmsBlockForm' => $this->getFactory()->createToggleActiveCmsBlockForm()->createView(),
        ]);
    }

    protected function saveCmsBlockCustomerGroups(FormInterface $cmsBlockForm): void
    {
        $this->getFactory()
            ->getCmsBlockCustomerGroupFacade()
            ->saveCmsBlockCustomerGroups($cmsBlockForm->getData());
    }
}
