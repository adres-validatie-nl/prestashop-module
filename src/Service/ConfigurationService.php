<?php

namespace PrestaShop\Module\AdresValidatie\Service;

use Configuration;

class ConfigurationService {
    private const CONFIGURATIONS = [
        'account_email' => 'ADRESVALIDATIE_ACCOUNT_EMAIL',
        'client_id' => 'ADRESVALIDATIE_CLIENT_ID',
        'client_secret' => 'ADRESVALIDATIE_CLIENT_SECRET',
        'hmac_secret' => 'ADRESVALIDATIE_HMAC_SECRET',
        'access_token' => 'ADRESVALIDATIE_ACCESS_TOKEN',
        'subscription_status' => 'ADRESVALIDATIE_SUBSCRIPTION_STATUS',
        'subscription_ends_at' => 'ADRESVALIDATIE_SUBSCRIPTION_ENDS_AT',
    ];

    /**
     * @param string $key
     * @return false|string
     */
    public function get($key)
    {
        return Configuration::get($this->getName($key));
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function set($key, $value)
    {
        Configuration::updateValue($this->getName($key), $value);
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function delete($key)
    {
        Configuration::deleteByName($this->getName($key));
        return $this;
    }

    /**
     * @return $this
     */
    public function deleteAll()
    {
        foreach (self::CONFIGURATIONS as $key) {
            Configuration::deleteByName($key);
        }
        return $this;
    }

    /**
     * @param string $key
     * @return string
     */
    private function getName($key)
    {
        if (!array_key_exists($key, self::CONFIGURATIONS)) {
            throw new \BadMethodCallException("Configuration key $key does not exist.");
        }

        return self::CONFIGURATIONS[$key];
    }
}
