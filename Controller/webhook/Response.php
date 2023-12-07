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

use Magento\Framework\Controller\ResultFactory;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use \Magento\Quote\Model\QuoteFactory as QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use \stdClass;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;


class Response extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    protected $request;
    
    const USER_NOTIF = 'payment/argenmagento/user_notif';
    
    const PASW_NOTIF = 'payment/argenmagento/pasw_notif';
    
    private $_quote;
    
    private $modelCart;
    
    private $orderRepository;
    
    private $quoteManagement;
    
    private $eventManager;
    
    private $maskedQuoteIdToQuoteId;
    
    protected $_urlInterface;
    
    protected $urlBuilder;
    
    protected $transactionBuilder;
    
    protected $orderSender;
    
    protected $_quoteFactory;
    
    protected $checkoutSession;
    
    protected $customerSession;

    protected $quoteIdMaskFactory;

    protected $_jsonResultFactory;

    protected $quoteRepository;
    
    protected $logger;
    
    protected $jsonResultFactory;
    
    
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        \Magento\Sales\Model\Order $order,
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ScopeConfig $scopeConfig,
        OrderSender $orderSender,
        JsonFactory $jsonResultFactory,
        \Magento\Checkout\Model\Cart $modelCart
    )
    {
        parent::__construct($context);
        $this->request = $request;
        $this->logger = $logger;
        $this->urlBuilder = $context->getUrl();
        $this->_objectManager = $objectManager;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->_quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->transactionBuilder = $transactionBuilder;
        $this->eventManager = $eventManager;
        $this->modelCart = $modelCart;
        $this->scopeConfig = $scopeConfig;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->jsonResultFactory = $jsonResultFactory;
        
    }
    
    
    
    
    /**
     * Create Csrf Validation Exception.
     *
     * @param RequestInterface $request
     *
     * @return null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        if ($request) {
            return null;
        }
    }

    /**
     * Validate For Csrf.
     *
     * @param RequestInterface $request
     *
     * @return bool true
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        if ($request) {
            return true;
        }
    }
    
    /**
     *
     * *
     */
    public function execute()
    {
        $responseGetnet = $this->request->getContent();
        
        $this->logger->info('---------RESULTADO BODY RESPONSE. (responseGetnet) -----------');
        $this->logger->debug($responseGetnet);
        
        $resultRedirect="";
        $ordenItems="";
        $auth = '';
        $message = '';
        $webMessage = '';
        $amount= '0';
       
       
        try {
            $jsondata=json_decode($responseGetnet , true);


            $email          = $jsondata["customer"]["email"];
            $customerName   = $jsondata["customer"]["name"];
            $method         = $jsondata["payment"]["method"];
            $amount         = $jsondata["payment"]["amount"];
            $currency       = $jsondata["payment"]["currency"];
            $status         = $jsondata["payment"]["result"]["status"];
            
            $this->logger->debug($amount);
            $this->logger->debug($currency);
            $this->logger->info('Status response: ' . $status);
            
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $user =    $this->scopeConfig->getValue(self::USER_NOTIF, $storeScope);
            $pasw =    $this->scopeConfig->getValue(self::PASW_NOTIF, $storeScope);
                        
            $basicOrigen = 'Basic ' .base64_encode($user . ':' . $pasw);
//            $this->logger->info('credentials plugin -->  '.$basicOrigen);
            
            $originHeader = $this->getRequest()->getHeader('Authorization');
//            $this->logger->info('credentials webhook Getnet -->  '.$originHeader);
            
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
            $orderDatamodel = $orderDatamodel->addFieldToFilter('customer_email', ['eq' => $email])->getLastItem();
                
            $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderDatamodel->getId());
            $this->logger->info('OrderID --> ' .$order->getId());
            
            ///////////////////////////////////////////////////////////////////////////////
            //////////////////////// Valida autenticaciones ///////////////////////////////
            if($basicOrigen == $originHeader){
                
                $this->logger->info("-- validation webhook success --");
                
                // The message was validated with the signature
                if ($status == 'Authorized'){
                    
                    $tokenID    = $jsondata["payment"]["payment_method"]["token_id"];
                    $paymentID  = $jsondata["payment"]["result"]["payment_id"];
                    $authCode   = $jsondata["payment"]["result"]["authorization_code"];
                    
                    try{
                        $shippingAmount = $jsondata["shipping"]["shipping_amount"];
                        $this->logger->debug('Shipping cost --> ' . $shippingAmount);
                    } catch (\Exception $e) {
                        $shippingAmount = 0;
                    }
                    
                    try{
                        $interes = $jsondata["payment"]["installment"]["interest_rate"];
                        $this->logger->debug('interes --> ' .$interes);
                    } catch (\Exception $e) {
                        $interes = 0;
                    }
                    
                    $grandTotal = ($amount + $shippingAmount + $interes) / 100;
                    $baseGrandTotal = ($amount) / 100;

                    $this->logger->info('Total --> '.$order->getGrandTotal().'');
                    
                    $status = $order->getState();
                    $payment = $order->getPayment();
                    
                    if($status != 'processing'){

                        if ($order) {
                                try {                                                                        

                                    $this->checkoutSession->setForceOrderMailSentOnSuccess(true);

                                    $this->checkoutSession
                                    ->setLastOrderId($order->getId())
                                    ->setLastRealOrderId($order->getIncrementId())
                                    ->setLastOrderStatus($order->getStatus());

                                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                                    $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                                    $order->setGrandTotal($grandTotal);
                                    
                                    
                                    try{
                                        if($interes !== 0){
                                            $order->addStatusHistoryComment(__('Payment process with ').$method .' , paymentID: ' .$paymentID .' - Authorization Code: ' . $authCode .'  >>>>> Interes: ' . $interes/ 100 , false);
                                        } else {
                                            $order->addStatusHistoryComment(__('Payment process with ').$method .' , paymentID: ' .$paymentID .' - Authorization Code: ' . $authCode, false);
                                            
                                        }
                                        $order->save();
                                    } catch (\Exception $e) {
                                        $this->logger->error('Error Capture');
                                    }
                                    
                                    
                                    $payment = $order->getPayment();
                                    $payment->setLastTransId($paymentID);
                                    $payment->setIsTransactionClosed(true);
                                    $payment->setShouldCloseParentTransaction(true);
                                    $transaction = $payment->addTransaction(
                                        \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH
                                        );
                                    
                                    $payment->setIsTransactionPending(false);
                                    $payment->setIsTransactionApproved(true);
                                    
                                    $addresss = $objectManager->get('\Magento\Customer\Model\AddressFactory');
                                    $address = $addresss->create();
                                    $address = $order->getBillingAddress();
                                    $address->setIsDefaultBilling(false)
                                    ->setIsDefaultShipping('1')
                                    ->setSaveInAddressBook('1');
                                    $address->save();
                                    
                                    //for Refund and Capture
                                    $domain = $this->urlBuilder->getRouteUrl('argenmagento');
        
                                    
                                    $payment->setAdditionalInformation('domain', $domain);
                                    $payment->setAdditionalInformation('status', $status);
                                    $payment->setAdditionalInformation('authCode', $authCode);
                                    $payment->setAdditionalInformation('interes', $interes);
                                    $payment->setAdditionalInformation('paymentID', $paymentID);
                                    $payment->setAdditionalInformation('method', 'argenmagento');
                                    $payment->setAdditionalInformation('pagoAprobado', 'si');
                                    
        
                                    $transaction = $this->transactionBuilder->setPayment($payment)
                                    ->setOrder($order)
                                    ->setTransactionId($paymentID)
                                    ->setAdditionalInformation($payment->getTransactionAdditionalInfo())
                                    ->build(Transaction::TYPE_AUTH);
                                    
                                    
                                    $payment->setParentTransactionId(null);
                                    $payment->save();
                                    
                                    //send email
                                    $this->orderSender->send($order);
                                    
                                    
                                    //  CREATE INVOICE
                                    $invoice = $objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
                                    $invoice = $invoice->setTransactionId($payment->getTransactionId())
                                    ->addComment("Invoice created.")
                                    ->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                                    
                                    $invoice->setGrandTotal($grandTotal);
                                    $invoice->setBaseGrandTotal($baseGrandTotal);
                                    
                                    $invoice->register()->pay();
                                    $invoice->save();
                                    
                                    
                                    // Save the invoice to the order
                                    $transaction = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                                    ->addObject($invoice)
                                    ->addObject($invoice->getOrder());
                                    $transaction->save();
                                    
                                    
                                    $order->addStatusHistoryComment(__('Invoice #%1.', $invoice->getId()) )
                                    ->setIsCustomerNotified(true);
                                    
                                    $order->save();
                                    $transaction->save();
                                    
                                    $this->logger->debug('guardo transacción');
                                    
                                    if ($order) {
                                        $this->logger->debug('-order-');
                                        $this->checkoutSession->setLastOrderId($order->getId())
                                        ->setLastRealOrderId($order->getIncrementId())
                                        ->setLastOrderStatus($order->getStatus());
                                    }
                                    
                                    $this->messageManager->addSuccessMessage(__('Your payment was processed correctly'));
                                    
                                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                                    $this->messageManager->addErrorMessage(__('Error while saving the transaction'));
                                }
                                
                                //eliminando cart
                                $cart = $this->modelCart;
                                $cart->truncate();
                                $cart->save();
                                
                                $webMessage = 'Webhook received successfully.';
                                
                                $this->logger->debug('Finished order complete callback.');
                            } // finaliza el $order
                    
                    } else {
                        $webMessage = 'Webhook -> Duplicate notification';
                        $this->logger->info('Webhook -> Duplicate notification');
                        
                    }   // finaliza la validacion para duplicidad
                    
                } else { //Error in response
                        $webMessage = $status . ' status --  > Payment declined <';
                    try{
                        $message = $jsondata["payment"]["result"]["return_message"];

                        $order->setState('getnet_rejected');
                        $order->setStatus('getnet_rejected');
                        
//                        $this->messageManager->addErrorMessage(__('Payment declined'));
                        $order->addStatusHistoryComment(__('Payment declined') . '--> ' . $message, false);
                        $order->save();
                    } catch (\Exception $e) {
                        $this->logger->error($e);
                    }
                    
                    $this->logger->info('Payment Denied');
                }
            } else {
                $this->logger->info('Validation Webhook - bad credentials');
                $webMessage = 'Validation Webhook Error';
                $order->addStatusHistoryComment($webMessage, false);
                $order->save();                
            }
            
        } catch (\Exception $e) {
            $this->logger->info($e);
        }
        
        
        // Responder con una confirmación
        $response = $this->jsonResultFactory->create();
        $response->setData(['status' => 'success', 'message' => $webMessage]);
        return $response;
        
    }
    
}