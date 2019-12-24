<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore
 */
class PickupStore extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore */
    protected $accountPickupStore = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore');
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Account\PickupStore|\Magento\Framework\Model\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAccountPickupStore()
    {
        if ($this->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if ($this->accountPickupStore !== null) {
            return $this->accountPickupStore;
        }

        return $this->accountPickupStore = $this->activeRecordFactory->getObjectLoaded(
            'Ebay_Account_PickupStore',
            $this->getAccountPickupStoreId()
        );
    }

    //########################################

    public function getListingProductId()
    {
        return (int)$this->getData('listing_product_id');
    }

    public function getAccountPickupStoreId()
    {
        return (int)$this->getData('account_pickup_store_id');
    }

    public function isProcessRequired()
    {
        return (int)$this->getData('is_process_required');
    }

    //########################################
}
