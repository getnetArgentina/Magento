<?php
/**
 * Plugin Name:       Magento GetNet
 * License:           Copyright © 2023 PagoNxt Merchant Solutions S.L. and Santander España Merchant Services, Entidad de Pago, S.L.U.
 * You may not use this file except in compliance with the License which is available here https://opensource.org/licenses/AFL-3.0 
 * License URI:       https://opensource.org/licenses/AFL-3.0
 *
 */
Namespace GetnetArg\Payments\Cron;

use Psr\Log\LoggerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class CancelaRejectedCron {
    
    protected $orderFactory;
    protected $dateTime;
    protected $logger;
    public    $_objectManager;

    public function __construct(
            \Magento\Framework\ObjectManagerInterface $_objectManager,
            LoggerInterface $logger,
            OrderFactory $orderFactory,
            DateTime $dateTime
    ) {
        $this->_objectManager  = $_objectManager;
        $this->orderFactory    = $orderFactory;
        $this->logger          = $logger;
        $this->dateTime        = $dateTime;
   }

   /**
    * Write to system.log
    *
    * @return void
    */
    public function execute() {
        $this->logger->info('---------------------------------------');
        $this->logger->info('Corriendo Cron Getnet.');
                
        $currentTime = new \DateTime();
        $oneHourAgo = clone $currentTime;
        $oneHourAgo->sub(new \DateInterval('PT1H'));

        $twentyFourHoursAgo = clone $currentTime;
        $twentyFourHoursAgo->sub(new \DateInterval('PT24H'));
        
        $orders = $this->orderFactory->create()->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('status', 'getnet_rejected')
            ->addFieldToFilter('created_at', ['gteq' => $twentyFourHoursAgo->format('Y-m-d H:i:s')])
            ->addFieldToFilter('created_at', ['lteq' => $oneHourAgo->format('Y-m-d H:i:s')]);

        foreach ($orders as $order) {
            $this->logger->info('Iniciando cambio de status de idOrden -> ' .$order->getId());
            $this->logger->info('Total -> ' .$order->getGrandTotal());
	                  
            $order->cancel();
            $order->addStatusToHistory('canceled',__('Order canceled automatically due to reject payment.'), false);
            $order->save();
        }       

        $this->logger->info('---------------------------------------');
    }
}