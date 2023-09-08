<?php

namespace Ess\M2ePro\Model\Amazon;

class ShippingMap extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    public const STANDARD = 'Standard';
    public const FREE_ECONOMY = 'FreeEconomy';
    public const EXPEDITED = 'Expedited';
    public const NEXT_DAY = 'NextDay';
    public const SAME_DAY = 'SameDay';
    public const SECOND_DAY = 'SecondDay';
    public const SCHEDULED = 'Scheduled';
    public const DOMESTIC = 'Domestic';
    public const INTERNATIONAL = 'International';

    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\ShippingMap::class);
    }

    public function getId()
    {
        return $this->getData($this->_idFieldName);
    }

    public function getAmazonCode()
    {
        return $this->getData('amazon_code');
    }

    public function getMagentoCode()
    {
        return $this->getData('magento_code');
    }

    public function getLocation()
    {
        return $this->getData('location');
    }

    public function getMarketplaceId()
    {
        return $this->getData('marketplace_id');
    }

    public function setAmazonCode($value)
    {
        return $this->setData('amazon_code', $value);
    }

    public function setMagentoCode($value)
    {
        return $this->setData('magento_code', $value);
    }

    public function setLocation($value)
    {
        return $this->setData('location', $value);
    }

    public function setMarketplaceId($value)
    {
        return $this->setData('marketplace_id', $value);
    }
}
