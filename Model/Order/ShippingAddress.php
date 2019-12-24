<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Provides simple API to work with address information from the order.
 */
namespace Ess\M2ePro\Model\Order;

/**
 * Class \Ess\M2ePro\Model\Order\ShippingAddress
 */
abstract class ShippingAddress extends \Magento\Framework\DataObject
{
    protected $countryFactory;
    /** @var \Ess\M2ePro\Model\Order */
    protected $order;

    /** @var \Magento\Directory\Model\Country */
    protected $country;

    /** @var \Magento\Directory\Model\Region */
    protected $region;

    /** @var \Magento\Directory\Helper\Data*/
    protected $directoryHelper;

    //########################################

    public function __construct(
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Ess\M2ePro\Model\Order $order,
        array $data = []
    ) {
        $this->countryFactory = $countryFactory;
        $this->directoryHelper = $directoryHelper;
        $this->order = $order;
        parent::__construct($data);
    }

    //########################################

    abstract public function getRawData();

    public function getCountry()
    {
        if ($this->country === null) {
            $this->country = $this->countryFactory->create();

            try {
                $this->country->loadByCode($this->getData('country_code'));
            } catch (\Exception $e) {
            }
        }

        return $this->country;
    }

    public function getRegion()
    {
        if (!$this->getCountry()->getId()) {
            return null;
        }

        if ($this->region === null) {
            $countryRegions = $this->getCountry()->getRegionCollection();
            $countryRegions->getSelect()->where('code = ? OR default_name = ?', $this->getState());

            $this->region = $countryRegions->getFirstItem();

            if ($this->isRegionValidationRequired() && !$this->region->getId()) {
                throw new \Ess\M2ePro\Model\Exception(
                    sprintf('State/Region "%s" in the shipping address is invalid.', $this->getState())
                );
            }

            $isRegionRequired = $this->directoryHelper->isRegionRequired($this->getCountry()->getId());

            if ($isRegionRequired && !$this->region->getId()) {
                $countryRegions = $this->getCountry()->getRegionCollection();
                $this->region = $countryRegions->getFirstItem();
            }
        }

        return $this->region;
    }

    /**
     * @return bool
     */
    public function isRegionValidationRequired()
    {
        return false;
    }

    public function getCountryName()
    {
        if (!$this->getCountry()->getId()) {
            return $this->getData('country_code');
        }

        return $this->getCountry()->getName();
    }

    public function getRegionId()
    {
        $region = $this->getRegion();

        if ($region === null || $region->getId() === null) {
            return null;
        }

        return $region->getId();
    }

    public function getRegionCode()
    {
        $region = $this->getRegion();

        if ($region === null || $region->getId() === null) {
            return '';
        }

        return $region->getCode();
    }

    protected function getState()
    {
        return $this->getData('state');
    }

    /**
     * @inheritdoc
     */
    public function isEmpty()
    {
        if (empty(array_filter($this->_data))) {
            return true;
        }
        return false;
    }

    //########################################
}
