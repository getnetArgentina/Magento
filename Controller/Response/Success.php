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
use Magento\Checkout\Model\Cart;
use \stdClass;


/**
 * Webhook Receiver Controller
 */
class Success extends \Magento\Framework\App\Action\Action
{
    
    protected $_request;
    
    protected $logger;
    
    protected $checkoutSession;
    
    /**
     * 
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Request\Http $request
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $request,
        Cart $cart,
        CheckoutSession $checkoutSession
        ) {
            $this->_request = $request;
            $this->cart = $cart;
            $this->checkoutSession = $checkoutSession;
            $this->logger = $logger;
            
            parent::__construct($context);
    }
    
    /**
     * Receives webhook events from Roadrunner
     */
    public function execute()
    {
        $this->logger->debug('----------------------------------------------');
        $this->logger->debug('-------------------Response Success-------------------');
        
        $order = $this->checkoutSession->getLastRealOrder();
        $this->logger->debug('-Last Order --> ' .$order->getIncrementId());
        
        try {
            sleep(1); //time for receive response
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/onepage/success');
            $this->logger->debug('Finished order complete controller.');
            
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }
        
        
        return $resultRedirect;
    }
    
}
