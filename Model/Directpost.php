<?php
/**
 * Plugin Name:       Magento GetNet
 * Plugin URI:        -
 * Description:       -
 * Version:           1.0
 * Author:            -
 * Author URI:        -
 * License:           Copyright © 2023 PagoNxt Merchant Solutions S.L. and Santander España Merchant Services, Entidad de Pago, S.L.U. 
 * You may not use this file except in compliance with the License which is available here https://opensource.org/licenses/AFL-3.0 
 * License URI:       https://opensource.org/licenses/AFL-3.0
 *
 */
namespace GetnetArg\Payments\Model;

class Directpost extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'argenmagento';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'argenmagento'; 

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * Check whether there are CC types set in configuration
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote)
        && $this->getConfigData('secret_id', $quote ? $quote->getStoreId() : null);
    }
}
