<?php
/**
 * Plugin Name:       Magento GetNet
 * Plugin URI:        -
 * Description:       -
 * License:           Copyright © 2023 PagoNxt Merchant Solutions S.L. and Santander España Merchant Services, Entidad de Pago, S.L.U. 
 * You may not use this file except in compliance with the License which is available here https://opensource.org/licenses/AFL-3.0 
 * License URI:       https://opensource.org/licenses/AFL-3.0
 *
 */
namespace GetnetArg\Payments\Controller\Response;


use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use \stdClass;


/**
 * Webhook Receiver Controller for Paystand
 */
class Cart extends \Magento\Framework\App\Action\Action
{
    
    protected $_request;
    
    protected $logger;
    
    protected $checkoutSession;
    
    protected $cart;

    protected $quoteRepository;

    private $orderRepository;
    
    protected $quoteFactory;
    
    protected $quoteIdMaskFactory;
    
    /**
     * 
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Request\Http $request
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $request,
        CheckoutSession $checkoutSession,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        array $data = []
        ) {
            parent::__construct($context);
            
            $this->_request = $request;
            $this->checkoutSession = $checkoutSession;
            $this->logger = $logger;
            $this->_objectManager = $objectManager;
            $this->quoteRepository = $quoteRepository;
            $this->orderRepository = $orderRepository;
            $this->cart = $cart;
            $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }
    
    /**
     * Receives webhook events from Roadrunner
     */
    public function execute()
    {
        $this->logger->debug('----------------------------------------------');
        $this->logger->debug('-------------------Return Cart-------------------');
   
        $order = $this->checkoutSession->getLastRealOrder();
        
        $email = $order->getCustomerEmail();
        
        $this->logger->debug('-Last Order --> ' .$order->getIncrementId());
        $this->logger->debug('email --> ' .$email);
        
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $configHelper = $objectManager->create('GetnetArg\Payments\Model\Cart');
        $configHelper->getCartItems($email);

        try {
              $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
              $resultRedirect->setPath('checkout/cart');
                 $this->logger->debug('Finished order fail');
            
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }
        
        return $resultRedirect;
    }
    
}