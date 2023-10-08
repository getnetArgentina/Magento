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
namespace GetnetArg\Payments\Controller\Webhook;
 
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;


class Refund extends \Magento\Framework\App\Action\Action
{
    
    const USER_PROCESS = 'payment/argenmagento/client_id';
    
    const PASW_PROCESS = 'payment/argenmagento/secret_id';
    
    const TEST_ENV = 'payment/argenmagento/test_environment';
    
    protected $messageManager;
    
    protected $logger;
    
    protected $resultPageFactory;
    
    protected $orderRepository;
    
    protected $orderSender;
    
    protected $checkoutSession;
        
    public $_objectManager;
    
        
    /**
     * @param \Magento\Framework\App\Action\Context       $context
     * @param \Magento\Framework\View\Result\PageFactory  $resultPageFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        CheckoutSession $checkoutSession
        ) {
            $this->_objectManager 	  = $_objectManager;
            $this->messageManager     = $messageManager;
            $this->resultPageFactory  = $resultPageFactory;
            $this->orderRepository    = $orderRepository;
            $this->orderSender        = $orderSender;
            $this->logger             = $logger;
            $this->checkoutSession    = $checkoutSession;
            
            parent::__construct($context);
    }
    /**
     * Detect Mobile view or Desktop View
     *
     * @return void
     */
    public function execute()
    {
        
        $orderId = $this->getRequest()->getParam('opCli');
        $orderId = base64_decode($orderId);
        
        $this->logger->debug('Entro Refund con id --> '.$orderId);
        $this->logger->debug('----------------------');
        
        $order = $this->orderRepository->get($orderId);
        $state = $order->getState();
        
        $this->logger->debug('Get status --> '.$state);
        
        //////////////////////////////////////////////////////
        //////// validate date for Cancel or Refund //////////
        //////////////////////////////////////////////////////
        $dateTrx = date_create($order->getCreatedAt());
        $dateTrx = date_format($dateTrx,"d/m/Y");
        $sysdate = date("d/m/Y");
        $this->logger->debug($dateTrx);
        
        
        $payment = $order->getPayment();
        $amount = $order->getGrandTotal();
        $shippingAmount = $order->getShippingAmount();
        $currency = $order->getOrderCurrencyCode();    
        
        $this->logger->debug('Currency. ' . $currency);
        
        
        if($state == 'processing'){
            
            $this->logger->debug('status --> ' . $state);
            $paymentID = $payment->getAdditionalInformation('paymentID');
            $this->logger->debug('paymentID --> ' . $paymentID);
            
            $testEnv = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue(self::TEST_ENV,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,);
            
            $user_postp = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue(self::USER_PROCESS,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,);
            
            $pasw_post = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue(self::PASW_PROCESS,\Magento\Store\Model\ScopeInterface::SCOPE_STORE,);
            
            
            if($dateTrx == $sysdate){ //Same day cancel
                $action = 'cancellation';
                $statusFinal = 'canceled';
                $jsonBody = '';
                
            } else { //Different day refund
                $action = 'refund';
                $statusFinal = 'getnet_refunded';
                
                 $amount = ($amount * 100);
                 $this->logger->debug('amountTotal: ' . $amount);
                 
                 $jsonBody = '{
                                "amount": '.$amount.'
                              }';
            }
            
            
            ////////////////////  GET TOKEN    //////////////////////
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            
            $configHelper = $objectManager->create('GetnetArg\Payments\Model\ClientWS');
            $token = $configHelper->getToken($user_postp, $pasw_post, $testEnv);
                    
            ///send POST
            $jsondata = $configHelper->getPostOperation($token, $jsonBody, $testEnv, $action, $paymentID);
            $this->logger->debug('$jsondata --> ' . $jsondata);

            try{                    
                    $result=json_decode($jsondata, true);

                    if (str_contains($jsondata, "It's not possible to cancel a payment with status Cancelled")){
                        $this->checkoutSession
                                ->setLastOrderId($order->getId())
                                ->setLastRealOrderId($order->getIncrementId())
                                ->setLastOrderStatus($order->getStatus());
                            $order->setState($statusFinal);
                            $order->setStatus($statusFinal);
                            
                            $order->cancel();
                            $order->save();

                    } else 
                                    
                    if (str_contains($jsondata, 'payment_id')) {
                            $newpayment_id = $result["payment_id"];
                            $newStatus     = $result["status"];
                            $newDate       = $result["transaction_datetime"];
                            $authCode      = $result["authorization_code"];
                            $authPaymentID = $result["authorization_payment_id"];
                            $olderPaymentID = $result["generated_by"];
                            $message = '';
                            
                                if ($newStatus == 'Refunded' || $newStatus == 'Cancelled') {
                                    $success = 'yes';
                                              $this->logger->debug('Success ' . $newStatus);
                                    
                                    $payment = $order->getPayment();
                                    $payment->setAdditionalInformation('newpayment_id', $newpayment_id);
                                    $payment->setAdditionalInformation('newStatus', $newStatus);
                                    $payment->setAdditionalInformation('authCode', $authCode);
                                    $payment->setAdditionalInformation('authPaymentID', $authPaymentID);
                                    $payment->save();
                                    
                                    $this->checkoutSession
                                        ->setLastOrderId($order->getId())
                                        ->setLastRealOrderId($order->getIncrementId())
                                        ->setLastOrderStatus($order->getStatus());

                                    $order->setState($statusFinal);
                                    $order->setStatus($statusFinal);


                                   	/////////RESTAURA EL STOCK /////////        
							        $stock= $objectManager->create('GetnetArg\Payments\Model\RestoreStock');
							        $bodyRequest = $stock->execute($order);


                                    $message = __('New Payment ID >> ') .$newpayment_id .', '. __('Status >> ') .$newStatus .', '. __('Authorization Code >> ') .$authCode .' --> ' .$newDate;
                                    $order->addStatusHistoryComment(__('Successful operation ') .' --> '. $message, false);
                                    $order->cancel();
                                    $order->save();
                                    
                                    $this->messageManager->addSuccessMessage(__('successful operation'));
                                    
                                } else {
                                    $message = __('Refund Fail');
                                    $success = 'no';
                                }
        
                            
                    } else if (str_contains($jsondata, 'message')) {
                            $message = $result["message"];
                            
                            $order->addStatusHistoryComment( __('Operation Fails '). ' --> ' . $message, false);
                            $order->save();
                            $this->messageManager->addErrorMessage( $message );
        
                    } else {
                        $order->addStatusHistoryComment(__('Invalid Operation '), false);
                        $order->save();
                        $this->messageManager->addErrorMessage(__('Invalid Operation '));
                            $this->logger->debug('Invalid Operation');
                    }
        
            } catch (\Exception $e) {
                            $this->logger->debug('Error Refund');
            }
            
        } else {
                $order->addStatusHistoryComment(__('Invalid Operation '), false);
                $order->save();
                $this->messageManager->addErrorMessage(__('Invalid Operation.'));
                $this->logger->debug('Invalid Operation');
        }
                    
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        $this->logger->debug('--- End refund --');
        
        return $resultRedirect;
    }
    
}