<?php

namespace Ess\M2ePro\Model\Walmart;

/**
 * @method \Ess\M2ePro\Model\Marketplace getParentObject()
 */
class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    private const CODE_CANADA = 'CA';
    private const CODE_UNITED_STATES = 'US';

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Walmart\Marketplace::class);
    }

    public function getCurrency()
    {
        return $this->getData('default_currency');
    }

    public function getDeveloperKey()
    {
        return $this->getData('developer_key');
    }

    public function getDefaultCurrency()
    {
        return $this->getData('default_currency');
    }

    public function isUnitedStates(): bool
    {
        return $this->getParentObject()->getCode() === self::CODE_UNITED_STATES;
    }

    public function isCanada(): bool
    {
        return $this->getParentObject()->getCode() === self::CODE_CANADA;
    }

    public function isSupportedProductType(): bool
    {
        return $this->isUnitedStates();
    }

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

    public function isCacheEnabled()
    {
        return true;
    }
}
