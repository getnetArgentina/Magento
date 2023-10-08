<?php
/**
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 *
 */
namespace GetnetArg\Payments\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Ramsey\Uuid\Uuid;

class OrderHelper extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    
    protected $logger;

    private $orderRepository;
    
    private $quoteManagement;
    
    protected $quoteRepository;
    
    /**
     * 
     * 
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Checkout\Model\Cart $modelCart,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->logger = $logger;
        $this->modelCart = $modelCart;
        $this->order = $order;
        $this->orderSender = $orderSender;

    }


    /**
     * 
     * 
     */
    public function getBodyOrderRequest($email)
    {
        $jsonBody ='-';
        $DNI = '9999999999'; //default
        
     try {
              $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
              
              $orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
              $orderDatamodel = $orderDatamodel->addFieldToFilter('customer_email', ['eq' => $email])->getLastItem();
               
              $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderDatamodel->getId());
        
              $quoteId = $order->getId();
              
              
              
              $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->addStatusHistoryComment(__('Se inicia intención de pago.'))
                ->setIsCustomerNotified(false);
                $order->save();

            /////////// Get basic elements //////////////
            $currency = $order->getOrderCurrencyCode();
            $amount = $order->getGrandTotal();
                $this->logger->debug('Amount  -->' .$amount . ' ' .$currency);
         

            $firstname = $order->getCustomerFirstname();
            $middlename = $order->getCustomerMiddlename();
            $lastname = $order->getCustomerLastname();

            
             $shippingAddress = $order->getShippingAddress();
                    $city_ship = substr($shippingAddress->getCity(), 0, 39);
                    $state_ship = substr($shippingAddress->getRegion(), 0, 19);
                    $telefono_ship = substr(str_replace("+", "", $order->getShippingAddress()->getTelephone()), 0, 14);
                    $streetName_ship = substr(preg_replace('/[^a-zA-Z]+/', ' ', $shippingAddress->getData('street')), 0, 59);
                    $streetNumber_ship = preg_replace('/[^0-9]+/', ' ', $shippingAddress->getData('street'));
                    $country_ship = substr($order->getShippingAddress()->getCountryId(), 0, 19);
                    $region_ship = $order->getShippingAddress()->getRegionCode ();
                    $shippingAmmount = $order->getShippingAmount();
                    $postCode_ship = $shippingAddress->getData('postcode');
                    $shippingAmmountCode = $shippingAddress->getData('shippingamount');
                    $firstname_ship = $shippingAddress->getFirstname();
                    $lastname_ship = $shippingAddress->getLastname();
                    
                    if($streetNumber_ship === ' '){
                        $streetNumber_ship = '0';
                    }

                    
            $billingAddress = $order->getBillingAddress($quoteId);
                    $streetName_bil = substr(preg_replace('/[^a-zA-Z ]+/', ' ', $billingAddress->getData('street')), 0, 59);
                    $streetNumber_bil = preg_replace('/[^0-9]+/', ' ', $billingAddress->getData('street'));
                    $city_bil = substr($billingAddress->getCity(), 0, 39);
                    $state_bil = substr($billingAddress->getRegion(), 0, 19);
                    $telefono_bil = substr(str_replace("+", "", $order->getBillingAddress()->getTelephone()), 0, 14);
                    $country_bil = substr($order->getBillingAddress()->getCountryId(), 0, 19);
                    $region_bil = $order->getBillingAddress()->getRegionCode ();
                    $postCode_bil = $billingAddress->getData('postcode');
                    

                    if($streetNumber_bil === ' '){
                        $streetNumber_bil = '0';
                    }
            

                    //seccion del DNI
                    try{
                        $DNI = $order->getDni();
                         
                        //No se capturo o el campo custom DNI no esta en Order
                        if($DNI == null || $DNI == ''){
                            $DNI = $shippingAddress->getData('dni');
                            
                            if($DNI == null || $DNI == ''){
                                $DNI = '9999999999'; //default
                            }
                        }
                        
                        if(strlen($DNI) < 8  || strlen($DNI) > 15){
                            $DNI = '9999999999'; //default
                        }
                        
                    } catch (\Exception $ee) {
                       $this->logger->debug($ee);
                   }        
            
            $uuid = Uuid::uuid4()->toString();

         
            $items = $order->getAllVisibleItems();

             $carritoBody = '';     
                    foreach ($items as $item) {
                        $taxAmount = $item->getBaseTaxAmount();
                        $quantity = str_replace(".0000", "", $item->getQtyOrdered());
                        $amountItems = str_replace(".0000", "", $item->getPrice());
                        $amountItems = $amountItems * 100;
                        
                        $carritoBody = $carritoBody.'{
                                "product_type": "digital_content",
                                "title": "'.$item->getName().'",
                                "description": "'.$item->getName().'",
                                "value": '.$amountItems.',
                                "quantity": '.$quantity.'
                            },';
                }
            
            
            $carritoBody = substr($carritoBody, 0, -1);    
                        

            $this->logger->debug('----------------Create Body Request-----------------');
            
            //request without decimals
            $amount = $amount * 100;
            $shippingAmmount = $shippingAmmount * 100;
            $amountBeforeShip = $amount - $shippingAmmount;
        
        
            //validate empty fields 
            if($city_bil !== ''){
                $city_bil = '"city": "'.$city_bil.'",';
            }
            
            if($state_bil !== ''){
                $state_bil = '"state": "'.$state_bil.'",';
            }
            
            if($city_ship !== ''){
                $city_ship = '"city": "'.$city_ship.'",';
            }
            
            if($state_ship !== ''){
                $state_ship = '"state": "'.$state_ship.'",';
            }
            
            if($telefono_ship !== ''){
                $telefono_ship = '"phone_number": "'.$telefono_ship.'",';
            }
            
            if($telefono_bil !== ''){
                $telefono_bil = '"phone_number": "'.$telefono_bil.'",';
            }
            
            
        
            $jsonBody = '{
                    "mode": "instant",
                    "payment": {
                        "amount": '.$amountBeforeShip.',
                        "currency": "'.$currency.'"
                    },
                    "product": [
                    '.$carritoBody.'
                    ],
                    "customer": {
                        "customer_id": "'.$uuid.'",
                        "first_name": "'.$firstname.'",
                        "last_name": "'.$lastname.'",
                        "name": "'.$firstname. ' '.$lastname.'",
                        "email": "'.$email.'",
                        "document_type": "dni",
                        "document_number": "'.$DNI.'", 
                        '.$telefono_bil.'
                        "checked_email": true,
                        "billing_address": {
                            "street": "'.$streetName_bil.'",
                            "number": "'.$streetNumber_bil.'",
                            '.$city_bil.''.$state_bil.'
                            "country": "'.$country_bil.'",
                            "postal_code": "'.$postCode_bil.'"
                        }
                    },
                    "shipping": {
                        "first_name": "'.$firstname_ship.'",
                        "last_name": "'.$lastname_ship.'",
                        "name": "'.$firstname_ship.' '.$lastname_ship.'", 
                        '.$telefono_ship.'
                        "shipping_amount": '.$shippingAmmount.',
                        "address": {
                            "street": "'.$streetName_ship.'",
                            "number": "'.$streetNumber_ship.'",
                            '.$city_ship.''.$state_ship.'
                            "country": "'.$country_ship.'",
                            "postal_code": "'.$postCode_ship.'"
                        }
                    },
                    "pickup_store": true,
                    "shipping_method": "PAC",
                	"authorization":"Bearer XXXXXXXXXX"
                }';


           } catch (\Exception $ee) {
                $this->logger->debug($ee);
          }
        
        return $jsonBody;

    }
    
    
    
    
    
    
    /**
     * 
     */
    public function divide($dividend, $divisor)
    {
        if (empty((float)$divisor)) {
            return (float)0;
        }

        return (float)($dividend / $divisor);
    }
    
    
   /**
     * 
     */ 
    public function calculateTax($taxAmount, $grossAmount, $decimals = 2)
    {
        return number_format(
            $this->divide($taxAmount, $grossAmount) * 100,
            $decimals
        );
    }
    
    

    
}
