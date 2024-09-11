<?php

namespace PrestaShop\Module\AdresValidatie\Service;

use OpenAPI\Client\Api\DefaultApi;
use PrestaShopLogger;

class ApiService
{
    /**
     * @var DefaultApi
     */
    private $apiInstance;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @param $authenticate
     * @return false|DefaultApi
     */
    public function getApiInstance($authenticate = true)
    {
        if (!isset($this->apiInstance)) {
            $config = \OpenAPI\Client\Configuration::getDefaultConfiguration();

            if (!$this->isProd()) {
                $config->setHost('http://localhost:5006');
            }

            $this->apiInstance = new DefaultApi(new \GuzzleHttp\Client(), $config);
        }

        if ($authenticate) {
            if (!$this->authenticate($this->apiInstance)) {
                return false;
            }
        }

        return $this->apiInstance;
    }

    /**
     * @param DefaultApi $apiInstance
     * @return bool
     */
    private function authenticate($apiInstance)
    {
        $config = $apiInstance->getConfig();
        $token = $config->getAccessToken();
        if (empty($token)) {
            $token = $this->configurationService->get('access_token');
        }
        if ($this->validateToken($token)) {
            $config->setAccessToken($token);
            return true;
        }

        try {
            $response = $apiInstance->accessTokenPost(
                $this->configurationService->get('client_id'),
                $this->configurationService->get('client_secret'),
            );

            $token = $response->getAccessToken();
            $config->setAccessToken($token);
            $this->configurationService->set('access_token', $token);

            return true;
        } catch (\Exception $e) {
            PrestaShopLogger::addLog('Guzzle error communicating with adres-validatie.api POST /access-token endpoint: "' . $e->getMessage() . '"', 0);
            return false;
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

    private function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $body = json_decode(base64_decode($parts[1]), true);
        if (empty($body)) {
            return false;
        }
        if (!isset($body['exp']) || $body['exp'] < time()) {
            return false;
        }

        return true;
    }
}