<?php

namespace PrestaShop\Module\AdresValidatie\Service;

use Context;

class AsyncService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * Calls a controller action with curl and hangs up immediately
     * @param string $controller
     * @param string $action
     * @return void
     */
    public function ajaxExecute($controller, $action)
    {
        error_log('ajaxExecute called ' . json_encode([$controller, $action]));
        $nonce = hash('sha512', uniqid() . rand(0, 1000000000));
        $this->configurationService->set('ajax_nonce', $nonce);

        error_log('setting nonce: "' . $nonce . '"');
        $url = Context::getContext()->link->getBaseLink() . "index.php?fc=module&module=adresvalidatie&controller=$controller&action=$action&nonce=$nonce";
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT_MS, 1);
        curl_exec($curl_handle);
        curl_close($curl_handle);
    }
}