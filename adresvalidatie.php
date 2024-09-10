<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdresValidatie extends Module
{
    public function __construct()
    {
        $this->name = 'adresvalidatie';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'adres-validatie.nl';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'Adres Validatie';
        $this->description = $this->l('Autocompletes the address forms for the Netherlands based on postcode and house number.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->get('router')->generate('adres_validatie_configuration'));
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        // TODO: migrate up
        // TODO: register hooks?
        // TODO: init configuration

        return true;
    }

    public function uninstall()
    {
        if(!parent::uninstall()) {
            return false;
        }

        // TODO: migrate down
        // TODO: delete configuration

        return true;
    }
}
