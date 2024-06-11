<?php
/**
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 *
 */
namespace GetnetArg\Payments\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Message\ManagerInterface;

class ClientWS extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    
    protected $_curl;
    
    protected $logger;
    
    protected $messageManager;
    
    /**
     * 
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->_curl = $curl;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }


    /**
     * 
     * 
     */
    public function getToken($ClientID, $Secret, $testEnv)
    {
        $accessToken ='';
        
        $this->logger->debug('----------------------------------------------');
        $this->logger->debug($ClientID);
        
        ////////////////////////////////////////////////////////
        ///////////////// GET Token/////////////////////////////
        
                //  1 --> Test Mode activated
         if($testEnv == '1'){ 
                           $url = 'https://api.pre.globalgetnet.com/authentication/oauth2/access_token';
                   
         } else { //produccion
                           $url = 'https://api.globalgetnet.com/authentication/oauth2/access_token';
                   
         }
         
         $this->logger->debug("url --> " . $url);

        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $ClientID,
            'client_secret' => $Secret,
        ];
        

        try {
            $this->_curl->addHeader("Content-Type", "application/x-www-form-urlencoded");
            $this->_curl->post($url, $data);
            $response = $this->_curl->getBody();
            
//            $this->logger->debug($response);
            
            if(str_contains($response, 'access_token')){
                $responseJson=json_decode($response , true);
                $accessToken = $responseJson['access_token'];
            } else {
                $accessToken = 'invalido';
            }
            
        } catch (\Magento\Framework\Exception\Exception $e) {
             throw new \Magento\Framework\Exception\LocalizedException(
                __('SORRY! There is a problem. Please contact us.')
            );
        }
        
        return $accessToken;

    }
    
    
    
    /**
     * GET Payment intent id
     * 
     */
    public function getPaymentIntentID($token, $bodyRequest, $testEnv){
        

        //  1 --> Test Mode activated
         if($testEnv == '1'){ 
                   $urlIntent = 'https://api.pre.globalgetnet.com/digital-checkout/v1/payment-intent';
                   
         } else { //produccion
                   $urlIntent = 'https://api.globalgetnet.com/digital-checkout/v1/payment-intent';
                   
         }
         

        $JsonArg = str_replace("XXXXXXXXXX", $token, $bodyRequest);
             $this->logger->debug("url --> " . $urlIntent);
             $this->logger->debug('Json Enviado --> ' . $JsonArg );

         try {
            $this->_curl->addHeader("Content-Type", "application/json");
            $this->_curl->addHeader("Authorization", "Bearer " . $token);
            $this->_curl->addHeader("Accept", "application/json");
            $this->_curl->post($urlIntent, $JsonArg);
            $response = $this->_curl->getBody();
        
                $this->logger->info('<-------------Response service payment GETNET-----------> ');
                $this->logger->info('payment Intent--> ' . $response );


            if(str_contains($response, 'payment_intent_id')){
                  $responseJson=json_decode($response , true);
                  $paymentIntent = $responseJson['payment_intent_id'];
                  
            } else if(str_contains($response, 'currency_not_allowed')){
                $paymentIntent = 'currency_error';
            } else {
                  $paymentIntent = 'error';
            }

      
        } catch (\Magento\Framework\Exception\Exception $e) {
                $this->logger->info($e);
                $this->logger->info($e);
                $this->messageManager->addError(__('Error al crear la intención de pago'));
        }
        
        return $paymentIntent;
        
    }
    
    
    
    
    
    /**
     * 
     * Cancel o Refund
     * 
     */
    public function getPostOperation($token, $jsonBody, $testEnv, $action, $paymentID)
    {
        $result ='';
        
        $this->logger->debug('----------------postOper--------------------');
        

        //  1 --> Test Mode activated
         if($testEnv == '1'){ 
                   $url = 'https://api.pre.globalgetnet.com/digital-checkout/v1/payments';
                   
         } else { //produccion
                   $url = 'https://api.globalgetnet.com/digital-checkout/v1/payments';
                   
         }
         
         $url = $url .'/'. $paymentID .'/'. $action;

        $this->logger->debug('url --> ' . $url);
        $this->logger->debug('body --> ' . $jsonBody);


        try {
            $this->_curl->addHeader("Content-Type", "application/json");
            $this->_curl->addHeader("Authorization", "Bearer " . $token);
            $this->_curl->addHeader("Accept", "application/json");
            $this->_curl->post($url, $jsonBody);
            $response = $this->_curl->getBody();
        
                $this->logger->debug('Response service Post Operation--> ' . $response);

        } catch (\Magento\Framework\Exception\Exception $e) {
                $this->logger->debug($e);
                $this->messageManager->addError('Error al intentar la operacion');
        }
        
        return $response;

    }
    
    
}
