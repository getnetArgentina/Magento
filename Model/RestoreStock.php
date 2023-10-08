<?php
/**
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 *
 */
namespace GetnetArg\Payments\Model;


class RestoreStock extends \Magento\Framework\View\Element\Template
{
    
    protected $logger;
    
    protected $moduleManager;
    
    protected $resourceConnection;
    
    public $_objectManager;

    
    /**
     * 
     * 
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Model\Order $order,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_objectManager     = $_objectManager;
        $this->moduleManager      = $moduleManager;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->order = $order;

    }


    /**
     * 
     * 
     */
    public function execute($order){
        
	     try{
                //if inventory is enabled
                if (!$this->isInventoryEnabled())
                    return;
        
                $this->logger->debug('isInventoryEnabled --> restore cart');
        
                $connection = $this->resourceConnection->getConnection();
                $stockId    = $this->_objectManager->get('\Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite');
                

                foreach ($order->getAllItems() as $item) {
                    $product = $item->getProduct();
                    
                    $quantity = $item->getQtyOrdered();
                    
                    $this->logger->debug('Quantity --> ' .$quantity);
                    
                    $metadata = [
                        'event_type'          => "back_item_qty",
                        "object_type"         => "legacy_stock_management_api",
                        "object_id"           => "",
                        "object_increment_id" => $order->getIncrementId()
                    ];
        
                    $query = "INSERT INTO inventory_reservation (stock_id, sku, quantity, metadata)
                        VALUES (".$stockId->execute().", '".$product->getSku()."', ".$quantity.", '".json_encode($metadata)."');"; 
        
                    //Insert data in db
                    $connection->query($query);
        
                    $this->logger->debug('inserto-----');
                }
            
                
            } catch (\Exception $e) {
                    $this->logger->debug(' exception --> ' . $e);
            }

    }
    
    
    
    
    
    
   /**
     * Check if Magento inventory feature is enabled.
     * 
     */
    public function isInventoryEnabled()
    {
        $requiredModules = [
            'Magento_Inventory',
            'Magento_InventoryApi',
            'Magento_InventoryCatalog',
            'Magento_InventorySalesApi',
            'Magento_InventorySalesApi',
        ];

        // Check if each required module is enabled
        foreach ($requiredModules as $module)
            if (!$this->moduleManager->isEnabled($module))
                return false;

        return true;
    }
        
}
