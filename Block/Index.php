<?php
namespace GetnetArg\Payments\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Request\Http;


class Index extends Template
{
    const CLIENT_ID = 'payment/argenmagento/client_id';
    
    const SECRET_ID = 'payment/argenmagento/secret_id';
    
    const TEST_ENV = 'payment/argenmagento/test_environment';
    
    private $checkoutSession;
    
    protected $logger;
    
    protected $request;
        
    protected $_checkoutSession;

    protected $_customerSession;
    
    protected $urlBuilder;

    /**
     * @param Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        Http $request,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }


    /**
     * @return string
     */
    public function getUrlSDK(){
        
        $test = \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
        ->getValue(self::TEST_ENV,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,);
        
//        $this->logger->debug('TEST ENV --> ' . $test);
        
             if($test == '1'){
                    $url = 'https://www.pre.globalgetnet.com/digital-checkout/loader.js';
               
             } else { //produccion
                    $url = 'https://www.globalgetnet.com/digital-checkout/loader.js';
             }
        
        return $url;
    }
    

    
    /**
     * @return string
     */
    public function getScript()
    {
        $this->logger->debug('------------------Init Script-------------------');
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        $clienId = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                ->getValue(self::CLIENT_ID,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,);

        $secret = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                ->getValue(self::SECRET_ID,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,);

        $testEnv = \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
        ->getValue(self::TEST_ENV,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,);

        /////////GET TOKEN /////////                
        $configHelper = $objectManager->create('GetnetArg\Payments\Model\ClientWS');
        $token = $configHelper->getToken($clienId, $secret, $testEnv);
        
        $Client_id = $this->request->getParam('prx');
        $email = base64_decode($Client_id);
            $this->logger->debug('ID Cliente --> ' .$email);



        /////////GET BODY /////////        
        $orderHelper = $objectManager->create('GetnetArg\Payments\Model\OrderHelper');
        $bodyRequest = $orderHelper->getBodyOrderRequest($email);

        
        
        
        /////////GET PAYMENT INTENT ID /////////
        $payIntentId = $configHelper->getPaymentIntentID($token, $bodyRequest, $testEnv);
        
         $this->logger->debug('$payIntentId --> ' .$payIntentId);
        
        if($payIntentId == 'error') {
            //enable cartItems
            $configHelper = $objectManager->create('GetnetArg\Payments\Model\Cart');
            $configHelper->getCartItems($email);
            $script = 'alert("Error al generar la intención de pago");
                        window.history.go(-2);';

        } else if($payIntentId == 'currency_error') {
            //enable cartItems
            $configHelper = $objectManager->create('GetnetArg\Payments\Model\Cart');
            $configHelper->getCartItems($email);
            $script = 'alert("Tipo de moneda no soportada");
                            window.history.go(-2);';
            
        }  else {
            $script = 'const config = {
                          "paymentIntentId": "'.$payIntentId.'",
                          "checkoutType": "iframe",
                               "accessToken": "Bearer '.$token.'"
                            };';
        }
        
        
//        $this->logger->debug($script);
        
        return $script;
    }
    
    
    
    /*
     * *
     */
        public function getURLreturn()
    {
        $baseURL = $this->urlBuilder->getBaseUrl();
            $this->logger->debug($baseURL);
            
        $UrlCart = $baseURL . 'argenmagento/response/cart';    

        return $UrlCart;
    }    
    
    
}