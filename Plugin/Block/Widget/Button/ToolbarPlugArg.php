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
declare(strict_types=1);

namespace GetnetArg\Payments\Plugin\Block\Widget\Button;

use Magento\Sales\Block\Adminhtml\Order\Create;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Toolbar as ToolbarContext;

class ToolbarPlugArg

{
    
    public function beforePushButtons(
        ToolbarContext $toolbar,
        AbstractBlock $context,
        ButtonList $buttonList
        
        ): array {
            $order = false;
            $state='';
            $nameInLayout = $context->getNameInLayout();
            try{
                if ('sales_order_edit' == $nameInLayout) {
                    $order = $context->getOrder();
                    $orderID = $order->getId();
                    $state = $order->getState();
                    $payment = $order->getPayment();
                    $domain = $payment->getAdditionalInformation('domain');
                }
                
                if ($order) {
                    
                    if($state == 'processing'){
                        $urlRefund = $domain.'webhook/refund?opCli='.base64_encode($orderID);
                        $message = __('Are you sure you want to refund this order?');
                        
                        $buttonList->add(
                            'refund_button',
                            [
                                'label' => __('Cancel and Refund'),
                                'onclick' => "confirmSetLocation('{$message}', '{$urlRefund}')",
                                'sort_order' => 3,
                                'id' => 'refund_button'
                                    ]
                            );
                        
                    }
                }
                
                
            } catch (\Exception $e) {
                $this->logger->debug('---error---'.$e.'-');
            }
            
            return [$context, $buttonList];
    }
}