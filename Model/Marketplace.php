<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * @method \Ess\M2ePro\Model\Ebay\Marketplace|\Ess\M2ePro\Model\Amazon\Marketplace getChildObject()
 */
class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Marketplace');
    }

    //########################################

    /**
     * @return bool
     */
    public function isLocked()
    {
        return true;
    }

    //########################################

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('marketplace');
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

        $orders = $this->getOrders(true);
        foreach ($orders as $order) {
            $order->delete();
        }

        $this->deleteChildInstance();

        $this->getHelper('Data\Cache\Permanent')->removeTagValues('marketplace');
        return parent::delete();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOtherListings($asObjects = false, array $filters = array())
    {
        $otherListings = $this->getRelatedComponentItems('Listing\Other','marketplace_id',$asObjects,$filters);

        if ($asObjects) {
            foreach ($otherListings as $otherListing) {
                /** @var $otherListing \Ess\M2ePro\Model\Listing\Other */
                $otherListing->setMarketplace($this);
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
        $orders = $this->getRelatedComponentItems('Order','marketplace_id',$asObjects,$filters);

        if ($asObjects) {
            foreach ($orders as $order) {
                /** @var $order \Ess\M2ePro\Model\Order */
                $order->setMarketplace($this);
            }
        }

        return $orders;
    }

    //########################################

    public function getIdByCode($code)
    {
        return $this->load($code,'code')->getId();
    }

    /**
     * @return bool
     */
    public function isStatusEnabled()
    {
        return $this->getStatus() == self::STATUS_ENABLE;
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getCode()
    {
        return $this->getData('code');
    }

    public function getUrl()
    {
        return $this->getData('url');
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getData('status');
    }

    public function getGroupTitle()
    {
        return $this->getData('group_title');
    }

    /**
     * @return int
     */
    public function getNativeId()
    {
        return (int)$this->getData('native_id');
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}