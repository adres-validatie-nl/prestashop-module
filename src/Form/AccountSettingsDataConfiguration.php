<?php

namespace PrestaShop\Module\AdresValidatie\Form;

use PrestaShop\Module\AdresValidatie\Service\ConfigurationService;
use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use Validate;

class AccountSettingsDataConfiguration implements DataConfigurationInterface
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    public function getConfiguration()
    {
        return [
            'client_id' => $this->configurationService->get('client_id'),
            'client_secret' => $this->configurationService->get('client_secret'),
            'hmac_secret' => $this->configurationService->get('hmac_secret'),
        ];
    }

    public function updateConfiguration(array $configuration): array
    {
        if (!$this->validateConfiguration($configuration)) {
            return [];
        }

        $errors = [];

        if (!Validate::isString($configuration['client_id']) || strlen($configuration['client_id']) !== 30) {
            $errors[] = 'Invalid client_id';
        }
        if (!Validate::isString($configuration['client_secret']) || strlen($configuration['client_secret']) !== 60) {
            $errors[] = 'Invalid client_secret';
        }
        if (!Validate::isString($configuration['hmac_secret']) || strlen($configuration['hmac_secret']) !== 60) {
            $errors[] = 'Invalid hmac_secret';
        }

        if (count($errors) > 0) {
            return $errors;
        }

        $this->configurationService->set('client_id', $configuration['client_id']);
        $this->configurationService->set('client_secret', $configuration['client_secret']);
        $this->configurationService->set('hmac_secret', $configuration['hmac_secret']);

        return [];
    }

    public function validateConfiguration(array $configuration): bool
    {
        return isset($configuration['client_id']) && isset($configuration['client_secret']) && isset($configuration['hmac_secret']);
    }
}
