<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon;

class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Marketplace');
    }
    
    //########################################

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('marketplace');
        return parent::save();
    }

    //########################################
    
    public function delete()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('marketplace');
        return parent::delete();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\ActiveRecord\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAmazonItems($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Amazon\Item','marketplace_id',$asObjects,$filters);
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\ActiveRecord\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDescriptionTemplates($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Amazon\Template\Description','marketplace_id',$asObjects,$filters);
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

    /**
     * @return bool
     */
    public function isAsinAvailable()
    {
        return (bool)$this->getData('is_asin_available');
    }

    /**
     * @return bool
     */
    public function isMerchantFulfillmentAvailable()
    {
        return (bool)$this->getData('is_merchant_fulfillment_available');
    }

    //########################################

    /**
     * @return bool
     */
    public function isNewAsinAvailable()
    {
        $newAsinNotImplementedMarketplaces = array(
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_CA,
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_JP,
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_CN,
        );

        return !in_array((int)$this->getId(),$newAsinNotImplementedMarketplaces);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }
    
    //########################################
}