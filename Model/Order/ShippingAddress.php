<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

abstract class ShippingAddress extends \Magento\Framework\DataObject
{
    /** @var \Magento\Directory\Model\CountryFactory */
    protected $countryFactory;
    /** @var \Ess\M2ePro\Model\Order */
    protected $order;
    /** @var \Magento\Directory\Model\Country */
    protected $country;
    /** @var \Magento\Directory\Model\Region */
    protected $region;
    /** @var \Magento\Directory\Helper\Data */
    protected $directoryHelper;

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

    abstract public function getRawData();

    abstract protected function isRegionOverrideRequired(): bool;

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

    /**
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getRegion()
    {
        if (!$this->getCountry()->getId()) {
            return null;
        }

        if ($this->region === null) {
            $countryRegions = $this->getCountry()->getRegionCollection();
            $countryRegions->getSelect()->where('code = ? OR default_name = ?', $this->getState());
            $this->region = $countryRegions->getFirstItem();
        }

        $isRegionRequired = $this->directoryHelper->isRegionRequired($this->getCountry()->getId());
        if ($isRegionRequired && !$this->region->getId()) {
            if (!$this->isRegionOverrideRequired()) {
                throw new \Ess\M2ePro\Model\Exception(
                    sprintf('Invalid Region/State value "%s" in the Shipping Address.', $this->getState())
                );
            }

            $countryRegions = $this->getCountry()->getRegionCollection();
            $this->region = $countryRegions->getFirstItem();
            $msg = ' Invalid Region/State value: "%s" in the Shipping Address is overridden by "%s".';
            $this->order->addInfoLog(sprintf($msg, $this->getState(), $this->region->getDefaultName()), [], [], true);
        }

        return $this->region;
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

    /**
     * @return bool
     */
    public function hasSameBuyerAndRecipient()
    {
        $rawAddressData = $this->order->getShippingAddress()->getRawData();

        $buyerNameParts = array_map('strtolower', explode(' ', $rawAddressData['buyer_name']));
        $recipientNameParts = array_map('strtolower', explode(' ', $rawAddressData['recipient_name']));

        $buyerNameParts = array_map('trim', $buyerNameParts);
        $recipientNameParts = array_map('trim', $recipientNameParts);

        sort($buyerNameParts);
        sort($recipientNameParts);

        return count(array_diff($buyerNameParts, $recipientNameParts)) == 0;
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
}
