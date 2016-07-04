<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

class PickupStore extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore */
    protected $accountPickupStore = NULL;

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
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if (!is_null($this->accountPickupStore)) {
            return $this->accountPickupStore;
        }

        return $this->accountPickupStore = $this->activeRecordFactory->getObjectLoaded(
            'Ebay\Account\PickupStore', $this->getAccountPickupStoreId()
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