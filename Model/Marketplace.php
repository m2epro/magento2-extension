<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use \Ess\M2ePro\Model\Ebay\Marketplace as EbayMarketplace;
use \Ess\M2ePro\Model\Amazon\Marketplace as AmazonMarketplace;
use \Ess\M2ePro\Model\Walmart\Marketplace as WalmartMarketplace;

/**
 * @method EbayMarketplace|AmazonMarketplace|WalmartMarketplace getChildObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Marketplace getResource()
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
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');
        return parent::save($reloadOnCreate);
    }

    //########################################

    public function getIdByCode($code)
    {
        return $this->load($code, 'code')->getId();
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
