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
    public function getConfigUrl()
    {
        return $this->urlBuilder->getUrl(
            'argenmagento/iframe/config',
            ['_secure' => true, '_query' => ['prx' => $this->request->getParam('prx')]]
        );
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