<?php

declare(strict_types=1);

namespace PrestaShop\Module\AdresValidatie\Controller;

use OpenAPI\Client\Api\DefaultApi;
use PrestaShop\Module\AdresValidatie\Service\ApiService;
use PrestaShop\Module\AdresValidatie\Service\ConfigurationService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationController extends FrameworkBundleAdminController
{
    public function index(Request $request): Response
    {
        /** @var ConfigurationService $configurationService */
        $configurationService = $this->get('prestashop.module.adresvalidatie.configuration_service');

        $accountEmail = $configurationService->get('account_email');
        if (!$accountEmail) {
            return $this->redirectToRoute('adres_validatie_account_email');
        }

        $formDataHandler = $this->get('prestashop.module.adresvalidatie.form.account_settings_form_data_handler');

        $form = $formDataHandler->getForm();
        $form->handleRequest($request);

        $templateData = [
            'accountEmail' => $accountEmail,
            'subscriptionStatus' => $configurationService->get('subscription_status'),
            'subscriptionEndsAt' => $configurationService->get('subscription_ends_at'),
            'changeAccountUrl' => $this->generateUrl('adres_validatie_account_email'),
            'checkoutUrl' => $this->generateUrl('adres_validatie_checkout'),
            'checkoutCancelUrl' => $this->generateUrl('adres_validatie_checkout_cancel'),
            'manageSubscriptionUrl' => $this->generateUrl('adres_validatie_manage_subscription'),
            'loginUrl' => $this->getPortalUrl() . '/auth/login?email=' . $accountEmail,
            'accountSettingsForm' => $form->createView(),
        ];

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Modules/adresvalidatie/views/templates/admin/configuration.html.twig', $templateData);
        }

        $errors = $formDataHandler->save($form->getData());
        if (!empty($errors)) {
            $this->flashErrors($errors);
            return $this->render('@Modules/adresvalidatie/views/templates/admin/configuration.html.twig', $templateData);
        }

        $this->addFlash('success', $this->trans('Your adres-validatie.nl account details have been updated.', 'Admin.Notifications.Success'));
        return $this->render('@Modules/adresvalidatie/views/templates/admin/configuration.html.twig', $templateData);
    }

    public function accountEmail(Request $request)
    {
        /** @var ConfigurationService $configurationService */
        $configurationService = $this->get('prestashop.module.adresvalidatie.configuration_service');

        $formDataHandler = $this->get('prestashop.module.adresvalidatie.form.account_email_form_data_handler');

        $form = $formDataHandler->getForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Modules/adresvalidatie/views/templates/admin/account_email_form.html.twig', ['emailForm' => $form->createView()]);
        }

        $formData = $form->getData();
        if (isset($formData['email']) && $formData['email'] === $this->getConfiguration()->get('ADRESVALIDATIE_ACCOUNT_EMAIL')) {
            return $this->redirectToRoute('adres_validatie_configuration');
        }

        $errors = $formDataHandler->save($form->getData());
        if (!empty($errors)) {
            $this->flashErrors($errors);
            return $this->render('@Modules/adresvalidatie/views/templates/admin/account_email_form.html.twig', ['emailForm' => $form->createView()]);
        }

        try {
            /** @var ApiService $apiService */
            $apiService = $this->get('prestashop.module.adresvalidatie.api_service');
            $apiResponse = $apiService->getApiInstance(false)->accountPost(
                $formData['email'],
                _PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?fc=module&module=adresvalidatie&controller=Webhook',
            );

            if ($apiResponse->getCode() === 'account-exists') {
                $this->addFlash('warning', $this->trans('An account already exists with this email. Try logging in to copy and paste your credentials below.', 'Admin.Notifications.Success'));

                $configurationService->delete('client_id');
                $configurationService->delete('client_secret');
                $configurationService->delete('hmac_secret');

                return $this->redirectToRoute('adres_validatie_configuration');
            }

            $account = $apiResponse->getAccount();
            $configurationService->set('client_id', $account->getClientId());
            $configurationService->set('client_secret', $account->getClientSecret());
            $configurationService->set('hmac_secret', $account->getHmacSecret());
        } catch (\Exception $e) {
            $this->getConfiguration()->remove('ADRESVALIDATIE_ACCOUNT_EMAIL');
            PrestaShopLogger::addLog('Guzzle error communicating with adres-validatie.api POST /account endpoint: "' . $e->getMessage() . '"', 0);
            $this->flashErrors(['received an error response from adres-validatie.nl, check the prestashop error log or try again later.']);
            return $this->render('@Modules/adresvalidatie/views/templates/admin/account_email_form.html.twig', ['emailForm' => $form->createView()]);
        }

        $this->addFlash('success', $this->trans('Your adres-validatie.nl account has been created.', 'Admin.Notifications.Success'));

        return $this->redirectToRoute('adres_validatie_configuration');
    }

    public function checkout(Request $request)
    {
        /** @var ConfigurationService $configurationService */
        $configurationService = $this->get('prestashop.module.adresvalidatie.configuration_service');

        try {
            /** @var DefaultApi $apiInstance */
            $apiInstance = $this->get('prestashop.module.adresvalidatie.api_service')->getApiInstance();

            $apiResponse = $apiInstance->stripeCheckoutSessionPost(
                $this->generateAbsoluteUrl('adres_validatie_configuration'),
                $this->generateAbsoluteUrl('adres_validatie_checkout_cancel'),
            );

            $configurationService->set('subscription_status', 'pending');

            return $this->redirect($apiResponse->getUrl());
        } catch (\Exception $e) {
            PrestaShopLogger::addLog('Guzzle error communicating with adres-validatie.api POST /stripe/checkout/session endpoint: "' . $e->getMessage() . '"', 0);
            $this->flashErrors(['received an error response from adres-validatie.nl, check the prestashop error log or try again later.']);
            return $this->redirectToRoute('adres_validatie_configuration');
        }
    }

    public function checkoutCancel(Request $request)
    {
        /** @var ConfigurationService $configurationService */
        $configurationService = $this->get('prestashop.module.adresvalidatie.configuration_service');

        if ($configurationService->get('subscription_status') === 'pending') {
            $configurationService->delete('subscription_status');
        }

        return $this->redirectToRoute('adres_validatie_configuration');
    }

    public function manageSubscription(Request $request)
    {
        try {
            /** @var DefaultApi $apiInstance */
            $apiInstance = $this->get('prestashop.module.adresvalidatie.api_service')->getApiInstance();

            $apiResponse = $apiInstance->stripePortalSessionPost(
                $this->generateAbsoluteUrl('adres_validatie_configuration'),
            );

            return $this->redirect($apiResponse->getUrl());
        } catch (\Exception $e) {
            PrestaShopLogger::addLog('Guzzle error communicating with adres-validatie.api POST /stripe/portal/session endpoint: "' . $e->getMessage() . '"', 0);
            $this->flashErrors(['received an error response from adres-validatie.nl, check the prestashop error log or try again later.']);
            return $this->redirectToRoute('adres_validatie_configuration');
        }
    }

    private function isProd()
    {
        // to detect my dev environment, HTTP_HOST = prestashop.localhost might not be specific enough
        if (
            $_SERVER['DOCUMENT_ROOT'] === '/home/daniel/projects/platforms/prestashop'
            && $_SERVER['SCRIPT_NAME'] === '/admin613lhosxiiwrv05zuzx/index.php'
        ) {
            return false;
        }
        return true;
    }

    private function getPortalUrl()
    {
        return $this->isProd() ? 'https://portal.adres-validatie.nl' : 'http://localhost:5005';
    }

    private function generateAbsoluteUrl($route)
    {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $this->generateUrl($route);
    }
}
