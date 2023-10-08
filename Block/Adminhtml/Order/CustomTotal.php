<?php

namespace GetnetArg\Payments\Block\Adminhtml\Order;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class CustomTotal extends Template
{
    protected $_template = 'GetnetArg_Payments::custom_total.phtml';

    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }


    public function getInterestAmount()
    {
        $order = $this->getOrder();
        $order = $this->initTotals();
/*        
        try{
         $payment = $order->getPayment();
         $currency = $order->getOrderCurrencyCode();
          
            $interes = $payment->getAdditionalInformation('interes');
            $interesFinal = $interes / 100;
        } catch (\Exception $e) {
            $interesFinal = 0;
        }
*/

         $interesFinal = 0;
        return $interesFinal;
    }
    
    
    
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $source = $parent->getSource();
        $order = $parent->getOrder();
        
        try{
         $payment = $order->getPayment();
         $currency = $order->getOrderCurrencyCode();
          
            $interes = $payment->getAdditionalInformation('interes');
            $interesFinal = $interes / 100;
        } catch (\Exception $e) {
            $interesFinal = 0;
        }
        
        $title = __('Interest Rate');

//        if($order->getCustomfee()!=0){
            $customAmount = new \Magento\Framework\DataObject(
                    [
                        'code' => 'customfee',
                        'strong' => false,
                        'value' => $interesFinal,
                        'label' => __($title),
                    ]
                );
            $parent->addTotal($customAmount, 'shipping');
//        }
        return $this;
    }
    
}