<?php

namespace PrestaShop\Module\AdresValidatie\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use Validate;

class AccountSettingsDataConfiguration implements DataConfigurationInterface
{
    public const CLIENT_ID_CONFIGURATION_KEY = 'ADRESVALIDATIE_CLIENT_ID';
    public const CLIENT_SECRET_CONFIGURATION_KEY = 'ADRESVALIDATIE_CLIENT_SECRET';
    public const HMAC_SECRET_CONFIGURATION_KEY = 'ADRESVALIDATIE_HMAC_SECRET';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration()
    {
        return [
            'client_id' => $this->configuration->get(static::CLIENT_ID_CONFIGURATION_KEY),
            'client_secret' => $this->configuration->get(static::CLIENT_SECRET_CONFIGURATION_KEY),
            'hmac_secret' => $this->configuration->get(static::HMAC_SECRET_CONFIGURATION_KEY),
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

        $this->configuration->set(static::CLIENT_ID_CONFIGURATION_KEY, $configuration['client_id']);
        $this->configuration->set(static::CLIENT_SECRET_CONFIGURATION_KEY, $configuration['client_secret']);
        $this->configuration->set(static::HMAC_SECRET_CONFIGURATION_KEY, $configuration['hmac_secret']);

        return [];
    }

    public function validateConfiguration(array $configuration): bool
    {
        return isset($configuration['client_id']) && isset($configuration['client_secret']) && isset($configuration['hmac_secret']);
    }
}
