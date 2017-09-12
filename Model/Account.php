<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * @method \Ess\M2ePro\Model\Ebay\Account|\Ess\M2ePro\Model\Amazon\Account getChildObject()
 */
class Account extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Account');
    }

    //########################################

    /**
     * @param bool $onlyMainConditions
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

    //########################################

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('account');
        return parent::save($reloadOnCreate);
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $otherListings = $this->getOtherListings(true);
        foreach ($otherListings as $otherListing) {
            $otherListing->delete();
        }

        if ($this->isComponentModeEbay() && $this->getChildObject()->isModeSandbox()) {
            $listings = $this->getRelatedComponentItems('Listing', 'account_id', true);
            foreach ($listings as $listing) {
                $listing->delete();
            }
        }

        $orders = $this->getOrders(true);
        foreach ($orders as $order) {
            $order->delete();
        }

        $this->deleteChildInstance();

        $this->getHelper('Data\Cache\Permanent')->removeTagValues('account');
        return parent::delete();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\Listing\Other[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOtherListings($asObjects = false, array $filters = array())
    {
        $otherListings = $this->getRelatedComponentItems('Listing\Other','account_id',$asObjects,$filters);

        if ($asObjects) {
            foreach ($otherListings as $otherListing) {
                /** @var $otherListing \Ess\M2ePro\Model\Listing\Other */
                $otherListing->setAccount($this);
            }
        }

        return $otherListings;
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOrders($asObjects = false, array $filters = array())
    {
        $orders = $this->getRelatedComponentItems('Order','account_id',$asObjects,$filters);

        if ($asObjects) {
            foreach ($orders as $order) {
                /** @var $order \Ess\M2ePro\Model\Order */
                $order->setAccount($this);
            }
        }

        return $orders;
    }

    //########################################

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

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}