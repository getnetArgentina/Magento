<?php
/**
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 *
 */
namespace GetnetArg\Payments\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Message\ManagerInterface;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order\Payment\Transaction;

class Cart extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    
    protected $logger;
    
    protected $messageManager;
    
    private $quoteManagement;
    
    protected $cart;

    private $orderRepository;

    protected $quoteRepository;
    
    protected $checkoutSession;
    
    protected $customerSession;
    
    protected $quoteFactory;

    
    /**
     * 
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        ManagerInterface $messageManager,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order $order,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Cart $cart,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        
    }


    /**
     * 
     * 
     */
    public function getCartItems($email)
    {
        try {                   
              $this->logger->debug('----Restore cart items----');
              
                $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
                $orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
                $orderDatamodel = $orderDatamodel->addFieldToFilter('customer_email', ['eq' => $email])->getLastItem();
                
                $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderDatamodel->getId());
            
                $quoteId = $order->getId();
                $this->logger->debug($quoteId);
                $this->logger->debug($order->getGrandTotal());

                $quote = $objectManager->create('Magento\Quote\Model\QuoteFactory')->create()->load($order->getQuoteId());
                $this->logger->debug('QuoteID -- ' .$quote->getId());

                //cancelamos la orden
                $this->cancelaOrden($order);

                $this->logger->debug('Restore -- ');
                
                //Restore cart
                $quote->setReservedOrderId(null);
                $quote->setIsActive(true);
                $quote->removePayment();
                $quote->save();
        
                $this->logger->debug( '-');
                
                $this->checkoutSession->replaceQuote($quote);
                $this->cart->setQuote($quote);
                $this->checkoutSession->restoreQuote();
                
                $this->logger->debug('-------- ');
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                   $this->logger->info('Error restore quote --> ' . $e);
            }
              

    }



    /**
     * Cancela orden
     */
    private function cancelaOrden($order)
    {
        $order->cancel();
        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
        $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED, 'Orden cancelada', false);
        $order->save();
    }


    
}
