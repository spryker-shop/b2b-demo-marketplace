<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Controller;

use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class RegistrationController extends AbstractController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function formAction(Request $request): array|\Symfony\Component\HttpFoundation\Response
    {
        $form = $this->getFactory()
            ->createTenantRegistrationForm()
            ->handleRequest($request);

        $successMessage = null;
        $errorMessage = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $tenantRegistrationTransfer = new TenantRegistrationTransfer();
            $tenantRegistrationTransfer->setCompanyName($data['companyName']);
            $tenantRegistrationTransfer->setTenantName($data['tenantName']);
            $tenantRegistrationTransfer->setEmail($data['email']);
            $tenantRegistrationTransfer->setPassword($data['password']);
            $tenantRegistrationTransfer->setDataSet($data['dataSetType']);

            $responseTransfer = $this->getFacade()->submitRegistration($tenantRegistrationTransfer);

            if ($responseTransfer->getIsSuccessful()) {
                $successMessage = 'Your tenant registration has been submitted successfully. You will receive an email notification once it is reviewed.';

                return $this->redirectResponse('/tenant-onboarding/registration/success');
            } else {
                $errors = [];
                foreach ($responseTransfer->getErrors() as $error) {
                    $errors[] = $error->getMessage();
                }
                $errorMessage = implode(', ', $errors);
            }
        }

        return $this->viewResponse([
            'form' => $form->createView(),
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function successAction(Request $request): array
    {
        return $this->viewResponse([
            'message' => 'Your tenant registration has been submitted successfully. You will receive an email notification once it is reviewed.',
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkEmailAction(Request $request): JsonResponse
    {
        $email = $request->get('email');

        if (!$email) {
            return $this->jsonResponse(['available' => false]);
        }

        $isAvailable = $this->getFacade()->isEmailAvailable($email);

        return $this->jsonResponse(['available' => $isAvailable]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkTenantNameAction(Request $request): JsonResponse
    {
        $tenantName = $request->get('tenantName');

        if (!$tenantName) {
            return $this->jsonResponse(['available' => false]);
        }

        $isAvailable = $this->getFacade()->isTenantNameAvailable($tenantName);

        return $this->jsonResponse(['available' => $isAvailable]);
    }
}
