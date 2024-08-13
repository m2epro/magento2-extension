<?php

namespace Ess\M2ePro\Model;

use Ess\M2ePro\Model\Amazon\Account as AmazonAccount;
use Ess\M2ePro\Model\Ebay\Account as EbayAccount;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;
use Ess\M2ePro\Model\ResourceModel\Account as ResourceAccount;

/**
 * @method AmazonAccount|EbayAccount|WalmartAccount getChildObject()
 */
class Account extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Account::class);
    }

    /**
     * @param bool $onlyMainConditions
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked($onlyMainConditions = false)
    {
        if ($this->isComponentModeEbay() && $this->getChildObject()->isModeSandbox()) {
            return false;
        }

        if (!$onlyMainConditions && parent::isLocked()) {
            return true;
        }

        return (bool)$this->activeRecordFactory->getObject('Listing')
                                               ->getCollection()
                                               ->addFieldToFilter('account_id', $this->getId())
                                               ->getSize();
    }

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('account');

        return parent::save($reloadOnCreate);
    }

    public function getListings()
    {
        return $this->getRelatedComponentItems('Listing', 'account_id', true);
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     *
     * @return array|\Ess\M2ePro\Model\Listing\Other[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOtherListings($asObjects = false, array $filters = [])
    {
        $otherListings = $this->getRelatedComponentItems('Listing\Other', 'account_id', $asObjects, $filters);

        if ($asObjects) {
            foreach ($otherListings as $otherListing) {
                /** @var \Ess\M2ePro\Model\Listing\Other $otherListing */
                $otherListing->setAccount($this);
            }
        }

        return $otherListings;
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOrders($asObjects = false, array $filters = [])
    {
        $orders = $this->getRelatedComponentItems('Order', 'account_id', $asObjects, $filters);

        if ($asObjects) {
            foreach ($orders as $order) {
                /** @var \Ess\M2ePro\Model\Order $order */
                $order->setAccount($this);
            }
        }

        return $orders;
    }

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getAdditionalData()
    {
        return $this->getData('additional_data');
    }

    /**
     * @return bool
     */
    public function isSingleAccountMode()
    {
        return $this->activeRecordFactory->getObject('Account')->getCollection()->getSize() <= 1;
    }

    // ----------------------------------------

    public function isCacheEnabled()
    {
        return true;
    }

    public function getCreateDate(): \DateTime
    {
        if ($this->getDataByKey(ResourceAccount::COLUMN_CREATE_DATE) === null) {
            throw new \LogicException('Create Date must be set');
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getDataByKey(ResourceAccount::COLUMN_CREATE_DATE)
        );
    }
}
