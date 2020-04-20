<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Model\Ebay\Account\PickupStore\State
 */
class State extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const IN_STOCK      = 'IN_STOCK';
    const OUT_OF_STOCK  = 'OUT_OF_STOCK';

    //########################################

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore */
    private $accountPickupStore = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Account\PickupStore\State');
    }

    //########################################

    public function getAccountPickupStore()
    {
        if ($this->accountPickupStore !== null) {
            return $this->accountPickupStore;
        }

        return $this->accountPickupStore = $this->activeRecordFactory
            ->getCachedObjectLoaded('Ebay_Account_PickupStore', $this->getAccountPickupStoreId());
    }

    //########################################

    public function getAccountPickupStoreId()
    {
        return (int)$this->getData('account_pickup_store_id');
    }

    public function isInProcessing()
    {
        return (bool)$this->getData('is_in_processing');
    }

    public function getSku()
    {
        return (string)$this->getData('sku');
    }

    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    public function getTargetQty()
    {
        return (int)$this->getData('target_qty');
    }

    public function getIsAdded()
    {
        return (bool)$this->getData('is_added');
    }

    public function getIsDeleted()
    {
        return (bool)$this->getData('is_deleted');
    }

    //########################################
}
