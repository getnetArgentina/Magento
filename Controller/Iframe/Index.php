<?php

namespace GetnetArg\Payments\Controller\Iframe;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory;
    
    protected $logger;

    /**
     * 
     */
    public function __construct(
            Context $context, 
            PageFactory $resultPageFactory,
            \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        
        $this->logger->debug('----------------------------------------------');
        $this->logger->debug('--------------Iframe After Order-------------------------'); 
        

        $resultPage = $this->resultPageFactory->create();
//        $resultPage->getConfig()->getTitle()->set(__('Getnet By Santander'));

        return $resultPage;
        
    }
}
