<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Marketplace
 */
class Marketplace extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_marketplace', 'marketplace_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}
