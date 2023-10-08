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

class OrderStatusPlugin
{

    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function beforePlace(
        \Magento\Sales\Model\Service\OrderService $subject,
        \Magento\Sales\Model\Order $order
    ) {

        $payment = $order->getPayment();

        $this->logger->info('------------------------------------');
        $this->logger->info('- Iniciando Orden --> ' .$payment->getMethod());

        if ($payment->getMethod() == 'argenmagento') {
            // Cambiar el estado de la orden a "pending_payment"
            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        }

        $this->logger->info('------------------------------------');         

        return [$order];
    }

}