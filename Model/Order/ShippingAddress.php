<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * Provides simple API to work with address information from the order.
 */
namespace Ess\M2ePro\Model\Order;

abstract class ShippingAddress extends \Magento\Framework\DataObject
{
    protected $countryFactory;
    /** @var \Ess\M2ePro\Model\Order */
    protected $order;

    /** @var \Magento\Directory\Model\Country */
    protected $country;

    /** @var \Magento\Directory\Model\Region */
    protected $region;

    //########################################

    public function __construct(
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Ess\M2ePro\Model\Order $order,
        array $data = []
    )
    {
        $this->countryFactory = $countryFactory;
        $this->order = $order;
        parent::__construct($data);
    }

    //########################################

    abstract public function getRawData();

    public function getCountry()
    {
        if (is_null($this->country)) {
            $this->country = $this->countryFactory->create();

            try {
                $this->country->loadByCode($this->getData('country_code'));
            } catch (\Exception $e) {}
        }

        return $this->country;
    }

    public function getRegion()
    {
        if (!$this->getCountry()->getId()) {
            return NULL;
        }

        if (is_null($this->region)) {
            $countryRegions = $this->getCountry()->getRegionCollection();
            $countryRegions->getSelect()->where('code = ? OR default_name = ?', $this->getState());

            $this->region = $countryRegions->getFirstItem();

            if ($this->isRegionValidationRequired() && !$this->region->getId()) {
                throw new \Ess\M2ePro\Model\Exception(
                    sprintf('State/Region "%s" in the shipping address is invalid.', $this->getState())
                );
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

        if (is_null($region) || is_null($region->getId())) {
            return 1;
        }

        return $region->getId();
    }

    public function getRegionCode()
    {
        $region = $this->getRegion();

        if (is_null($region) || is_null($region->getId())) {
            return '';
        }

        return $region->getCode();
    }

    protected function getState()
    {
        return $this->getData('state');
    }

    //########################################
}