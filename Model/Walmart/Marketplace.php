<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Marketplace getParentObject()
 */

namespace Ess\M2ePro\Model\Walmart;

/**
 * Class \Ess\M2ePro\Model\Walmart\Marketplace
 */
class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Marketplace');
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getWalmartItems($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Walmart\Item', 'marketplace_id', $asObjects, $filters);
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDescriptionTemplates($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Walmart_Template_Description', 'marketplace_id', $asObjects, $filters);
    }

    //########################################

    public function getCurrency()
    {
        return $this->getData('default_currency');
    }

    //########################################

    public function getDeveloperKey()
    {
        return $this->getData('developer_key');
    }

    public function getDefaultCurrency()
    {
        return $this->getData('default_currency');
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');
        return parent::save();
    }

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');
        return parent::delete();
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
