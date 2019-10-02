<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping;

/**
 * Class Calculated
 * @package Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping
 */
class Calculated extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_template_shipping_calculated', 'template_shipping_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}
