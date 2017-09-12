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
    public function isNewAsinAvailable()
    {
        return (bool)$this->getData('is_new_asin_available');
    }

    /**
     * @return bool
     */
    public function isMerchantFulfillmentAvailable()
    {
        return (bool)$this->getData('is_merchant_fulfillment_available');
    }

    /**
     * @return bool
     */
    public function isBusinessAvailable()
    {
        return (bool)$this->getData('is_business_available');
    }

    /**
     * @return bool
     */
    public function isVatCalculationServiceAvailable()
    {
        return (bool)$this->getData('is_vat_calculation_service_available');
    }

    /**
     * @return bool
     */
    public function isProductTaxCodePolicyAvailable()
    {
        return (bool)$this->getData('is_product_tax_code_policy_available');
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}