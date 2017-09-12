<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Account\PickupStore;

class Log extends \Ess\M2ePro\Model\ResourceModel\Log\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_account_pickup_store_log', 'id');
    }

    /**
     * @return string
     */
    public function getConfigGroupSuffix()
    {
        return 'ebay_pickup_store';
    }

    //########################################
}