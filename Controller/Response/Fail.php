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
class Fail extends \Magento\Framework\App\Action\Action
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
        $this->logger->debug('-------------------Response Fail-------------------');
    try {        
        $order = $this->checkoutSession->getLastRealOrder();
        
        $email = $order->getCustomerEmail();
        
        $this->logger->debug('-Last Order --> ' .$order->getIncrementId());
        $this->logger->debug('email --> ' .$email);
        
//        $this->messageManager->addErrorMessage(__('Payment Denied'));
        
        
            if ($order->getCustomerIsGuest()) {
                    $this->logger->debug('Customer guest --> ');

                    try{
                          $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
                          $orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
                          $orderDatamodel = $orderDatamodel->addFieldToFilter('customer_email', ['eq' => $email])->getLastItem();
                           
                          $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderDatamodel->getId());
                    
                          $quoteId = $order->getId();        
                          $this->logger->debug('ID object Manager:');
                          $this->logger->debug($quoteId);
                          $this->logger->debug($order->getGrandTotal());
                   
                          $quote = $this->quoteRepository->get($quoteId);
                          $order = $this->orderRepository->get($quoteId);

                          $quote = $objectManager->create('Magento\Quote\Model\QuoteFactory')->create()->load($order->getQuoteId());

                          $this->checkoutSession
                              ->setLastQuoteId($quote->getId())
                              ->setLastSuccessQuoteId($quote->getId())
                              ->clearHelperData();

                         $quote->setReservedOrderId(null);
                         $quote->setIsActive(true);
                         $quote->removePayment();
                         $quote->save();

        
                         $this->checkoutSession->replaceQuote($quote);
                         //OR add quote to cart
                         $this->cart->setQuote($quote);  
                         //if your last order is still in the session (getLastRealOrder() returns order data) you can achieve what you need with this one line without loading the order:
                         $this->checkoutSession->restoreQuote();
                        } catch (\Exception $e) {
                                  $this->logger->debug('- in ErrorCode -');
                        }
                    
             } else {
                 $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
                  $quoteId = $order->getId();
                  $quote = $this->quoteRepository->get($quoteId);
                  $order = $this->orderRepository->get($quoteId);
                  
                  $quote = $objectManager->create('Magento\Quote\Model\QuoteFactory')->create()->load($order->getQuoteId());
                  $this->logger->debug('$quoteId --> ' .$quoteId);
                  
                   $this->checkoutSession
                          ->setLastQuoteId($quote->getId())
                          ->setLastSuccessQuoteId($quote->getId())
                          ->clearHelperData();
    
                    $quote->setReservedOrderId(null);
                    $quote->setIsActive(true);
                    $quote->removePayment();
                    $quote->save();
    
                    $this->checkoutSession->replaceQuote($quote);
                    $this->cart->setQuote($quote);  

                    $this->checkoutSession->restoreQuote();
             }
             
	        /////////RESTAURA EL STOCK /////////        
	        $stock= $objectManager->create('GetnetArg\Payments\Model\RestoreStock');
	        $bodyRequest = $stock->execute($order);
	             
             
            $order->addStatusHistoryComment(__('The user canceled the payment flow'), false);
            $order->save();


        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->logger->debug('Error restore quote, redirect checkout cart');
        }


        try {
              $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
              $resultRedirect->setPath('checkout/cart');
                 $this->logger->debug('Finished order fail');
            
        } catch (\Exception $e) {
            $this->logger->debug($e);
            $this->logger->debug('Error redirect checkout/cart');
        }
        
        return $resultRedirect;
    }
    
}