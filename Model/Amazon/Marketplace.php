<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon;

/**
 * Class \Ess\M2ePro\Model\Amazon\Marketplace
 */
class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Marketplace::class);
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');

        return parent::save();
    }

    //########################################

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');

        return parent::delete();
    }

    //########################################

    public function getCurrency()
    {
        return $this->getData('default_currency');
    }

    //########################################

    public function getDefaultCurrency()
    {
        return $this->getData('default_currency');
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
