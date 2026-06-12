<?php
/**
 * Plugin Name:       Magento GetNet
 * Plugin URI:        -
 * Description:       -
 * License:           Copyright © 2023 PagoNxt Merchant Solutions S.L. and Santander España Merchant Services, Entidad de Pago, S.L.U.
 * You may not use this file except in compliance with the License which is available here https://opensource.org/licenses/AFL-3.0
 * License URI:       https://opensource.org/licenses/AFL-3.0
 *
 */
namespace GetnetArg\Payments\Controller\Iframe;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends Action
{
    const CLIENT_ID = 'payment/argenmagento/client_id';

    const SECRET_ID = 'payment/argenmagento/secret_id';

    const TEST_ENV = 'payment/argenmagento/test_environment';

    protected $resultJsonFactory;

    protected $scopeConfig;

    protected $urlBuilder;

    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setHeader('Pragma', 'no-cache', true);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $clientId = $this->scopeConfig->getValue(self::CLIENT_ID, ScopeInterface::SCOPE_STORE);
        $secret = $this->scopeConfig->getValue(self::SECRET_ID, ScopeInterface::SCOPE_STORE);
        $testEnv = $this->scopeConfig->getValue(self::TEST_ENV, ScopeInterface::SCOPE_STORE);

        $email = base64_decode((string) $this->getRequest()->getParam('prx'));

        if ($this->getOrderStatus($objectManager, $email) == 'processing') {
            return $result->setData([
                'status' => 'redirect',
                'url' => $this->urlBuilder->getBaseUrl() . 'checkout/onepage/success'
            ]);
        }

        $clientWS = $objectManager->create('GetnetArg\Payments\Model\ClientWS');
        $token = $clientWS->getToken($clientId, $secret, $testEnv);

        if ($token == 'invalido') {
            $objectManager->create('GetnetArg\Payments\Model\Cart')->getCartItems($email);
            return $result->setData([
                'status' => 'error',
                'message' => 'No se pudo generar la intención de pago, por favor intente de nuevo. Si el problema persiste, contacte con un ejecutivo Getnet.'
            ]);
        }

        $bodyRequest = $objectManager->create('GetnetArg\Payments\Model\OrderHelper')->getBodyOrderRequest($email);
        $payIntentId = $clientWS->getPaymentIntentID($token, $bodyRequest, $testEnv);

        if ($payIntentId == 'error') {
            $objectManager->create('GetnetArg\Payments\Model\Cart')->getCartItems($email);
            return $result->setData([
                'status' => 'error',
                'message' => 'Error al generar la intención de pago'
            ]);
        }

        if ($payIntentId == 'currency_error') {
            $objectManager->create('GetnetArg\Payments\Model\Cart')->getCartItems($email);
            return $result->setData([
                'status' => 'error',
                'message' => 'Tipo de moneda no soportada'
            ]);
        }

        return $result->setData([
            'status' => 'init',
            'paymentIntentId' => $payIntentId,
            'checkoutType' => 'iframe',
            'accessToken' => 'Bearer ' . $token
        ]);
    }

    private function getOrderStatus($objectManager, $email)
    {
        $orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
        $orderDatamodel = $orderDatamodel->addFieldToFilter('customer_email', ['eq' => $email])->getLastItem();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderDatamodel->getId());

        return $order->getStatus();
    }
}
