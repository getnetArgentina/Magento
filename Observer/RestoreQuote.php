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
namespace GetnetArg\Payments\Observer;

use Magento\Framework\Event\ObserverInterface;

class RestoreQuote implements ObserverInterface
{
     private $checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session\Proxy $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $lastRealOrder = $this->checkoutSession->getLastRealOrder();
        if ($lastRealOrder->getPayment()) {

            if ($lastRealOrder->getData('state') === 'new' && $lastRealOrder->getData('status') === 'pending_payment') {
                $this->checkoutSession->restoreQuote();
            }
        }
        return true;
    }
}