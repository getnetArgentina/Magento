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
namespace GetnetArg\Payments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class PaymentsConfigProvider implements ConfigProviderInterface
{
  /**
   * @var \Magento\Framework\App\Config\ScopeConfigInterface
   */

    protected $scopeConfig;

    const CLIENT_ID = 'payment/argenmagento/client_id';
    
    const SECRET_ID = 'payment/argenmagento/secret_id';
    
    const USER_NOTIF = 'payment/argenmagento/user_notif';
    
    const PASS_NOTIF = 'payment/argenmagento/pasw_notif';
    
    const TEST_PAYMENT = 'payment/argenmagento/test_environment';

 
    public function __construct(
        ScopeConfig $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }
 


    public function getConfig()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $config = [];

        return $config;
    }
}
