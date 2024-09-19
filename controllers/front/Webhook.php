<?php
// Prestashop does not support dependencies from the module namespace front-controller
include dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

use PrestaShop\Module\AdresValidatie\Service\ConfigurationService;
use PrestaShop\Module\AdresValidatie\Service\DatabaseService;
use Symfony\Component\HttpFoundation\Request;

class AdresValidatieWebhookModuleFrontController extends ModuleFrontController
{
    /**
     * @var ConfigurationService $configurationService
     */
    private $configurationService;

    /**
     * @var DatabaseService
     */
    private $databaseService;

    public function __construct()
    {
        parent::__construct();

        // Prestashop does not support auto-wiring for front-controllers
        $this->configurationService = new ConfigurationService();
        $this->databaseService = new DatabaseService();
    }

    public function initContent()
    {
        parent::initContent();

        $hmacSecret = $this->configurationService->get('hmac_secret');
        if ($hmacSecret === false) {
            echo 'Can not verify request as hmac_secret is not know on this end.';
            exit;
        }

        $request = Request::createFromGlobals();

        $requestTime = $request->headers->get('requesttime');
        $expiryTime = $request->headers->get('expirytime');
        $nonce = $request->headers->get('nonce');
        $hmacHash = $request->headers->get('hmachash');
        if (empty($requestTime)
            || empty($expiryTime)
            || empty($nonce)
            || empty($hmacHash)
        ) {
            echo 'Missing request header, expecting "requesttime", "expirytime", "nonce", and "hmachash".';
            exit;
        }

        if ((int) $request->headers->get('expirytime') < time()) {
            echo 'The request is expired.';
        }

        $uri =  $request->getSchemeAndHttpHost() . $request->getRequestUri(); // $request->getUri() sorts the querystring
        if ($hmacHash !== hash_hmac('sha256', "$uri\n$requestTime\n$expiryTime\n$nonce", $hmacSecret)) {
            echo 'The request hmac_hash does not match the expected hash. ';
            echo 'Either the hmac_secret does not match, or the request has been corrupted';
            exit;
        }

        $this->databaseService->deleteExpiredNonces();
        if ($this->databaseService->doesNonceExist($nonce)) {
            echo 'The request nonce has been used before. Possible replay attack';
            exit;
        }
        $this->databaseService->storeNonce($nonce, $expiryTime);

        $data = json_decode($request->getContent(), true);
        if (empty($data) || empty($data['message'])) {
            die('Request content could not be processed as JSON');
        }
        switch ($data['message']) {
            case 'account-updated':
                $this->handleAccountUpdated($data);
                break;
            case 'new-file':
                $this->handleNewFile($data);
                break;
            default:
                die('Unknown message: ' . $data['message']);
        }
    }

    private function handleAccountUpdated($data)
    {
        if (!array_key_exists('account', $data)
            || !array_key_exists('hasSubscription', $data['account'])
            || !array_key_exists('subscriptionEndsAt', $data['account'])
        ) {
            die('Invalid request content form message account-updated.');
        }

        $previousStatus = $this->configurationService->get('subscription_status');
        if ($data['account']['hasSubscription']) {
            $this->configurationService->set('subscription_status', 'active');
        } else {
            $this->configurationService->delete('subscription_status');
        }

        if ($data['account']['subscriptionEndsAt']) {
            $this->configurationService->set('subscription_ends_at', $data['account']['subscriptionEndsAt']);
        } else {
            $this->configurationService->delete('subscription_ends_at');
        }


        if ($data['account']['hasSubscription'] && $previousStatus !== 'active') {
            // TODO: start download, import
        }

        die('Message account-updated received.');
    }

    private function handleNewFile($data)
    {
        // TODO: if new file start download, import

        die('Message new-file received.');
    }
}