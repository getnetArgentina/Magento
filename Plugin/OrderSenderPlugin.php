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
namespace GetnetArg\Payments\Plugin;

class OrderSenderPlugin
{

    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function aroundSend(\Magento\Sales\Model\Order\Email\Sender\OrderSender $subject, callable $proceed, $order, $forceSyncMode = false)
    {

        $payment = $order->getPayment();

        $this->logger->info('------------------------------------');
        $this->logger->info('- Se envia correo --> ' .$payment->getMethod());

            if ($payment->getMethod() == 'argenmagento') {
                $flagApproved =  $payment->getAdditionalInformation('pagoAprobado');

                if($flagApproved == null){
                    $this->logger->info('-stop mail-');
                    return false;

                } else {
                    $this->logger->info('>>> send mail <<<');
                }
            }

        $this->logger->info('------------------------------------');   

        return $proceed($order, $forceSyncMode);
    }
}