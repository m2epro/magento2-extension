<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request;

class Shipping extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
{
    const SHIPPING_TYPE_FLAT       = 'flat';
    const SHIPPING_TYPE_CALCULATED = 'calculated';
    const SHIPPING_TYPE_FREIGHT    = 'freight';
    const SHIPPING_TYPE_LOCAL      = 'local';

    const MEASUREMENT_SYSTEM_ENGLISH = 'English';
    const MEASUREMENT_SYSTEM_METRIC  = 'Metric';

    const CROSS_BORDER_TRADE_NONE           = 'None';
    const CROSS_BORDER_TRADE_NORTH_AMERICA  = 'North America';
    const CROSS_BORDER_TRADE_UNITED_KINGDOM = 'UK';

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    private $shippingTemplate = NULL;

    private $calculatedShippingData = NULL;

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = array();

        if ($this->getConfigurator()->isGeneralAllowed()) {

            $data = array(
                'country' => $this->getShippingSource()->getCountry(),
                'address' => $this->getShippingSource()->getAddress(),
                'postal_code' => $this->getShippingSource()->getPostalCode()
            );

            if ($this->getShippingTemplate()->isLocalShippingFlatEnabled() ||
                $this->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {

                $data['dispatch_time'] = $this->getEbayListingProduct()->getShippingTemplate()->getDispatchTime();

                // there are permissions by marketplace (interface management)
                $data['cash_on_delivery_cost'] = $this->getShippingTemplate()->getCashOnDeliveryCost();

                // there are permissions by marketplace (interface management)
                if ($this->getShippingTemplate()->isCrossBorderTradeNorthAmerica()) {
                    $data['cross_border_trade'] = self::CROSS_BORDER_TRADE_NORTH_AMERICA;
                } else if ($this->getShippingTemplate()->isCrossBorderTradeUnitedKingdom()) {
                    $data['cross_border_trade'] = self::CROSS_BORDER_TRADE_UNITED_KINGDOM;
                } else {
                    $data['cross_border_trade'] = self::CROSS_BORDER_TRADE_NONE;
                }

                $data['excluded_locations'] = array();
                foreach ($this->getShippingTemplate()->getExcludedLocations() as $location) {
                    $data['excluded_locations'][] = $location['code'];
                }

                // there are permissions by marketplace (interface management)
                $data['global_shipping_program'] = $this->getShippingTemplate()->isGlobalShippingProgramEnabled();
            }
        }

        if ($this->getConfigurator()->isShippingServicesAllowed()) {

            $data = array_merge(
                $data,
                $this->getShippingData()
            );
        }

        return empty($data) ? array() : array('shipping' => $data);
    }

    //########################################

    /**
     * @return array
     */
    public function getShippingData()
    {
        $shippingData = array();

        $shippingData['local'] = $this->getLocalShippingData();

        if ($this->getShippingTemplate()->isLocalShippingLocalEnabled() ||
            $this->getShippingTemplate()->isLocalShippingFreightEnabled()) {
            return $shippingData;
        }

        if ($this->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {
            $shippingData['calculated'] = $this->getCalculatedData();
        }

        if ($this->getShippingTemplate()->isInternationalShippingFlatEnabled() ||
            $this->getShippingTemplate()->isInternationalShippingCalculatedEnabled()) {

            $shippingData['international'] = $this->getInternationalShippingData();

            if ($this->getShippingTemplate()->isInternationalShippingCalculatedEnabled()) {
                if (!isset($shippingData['calculated'])) {
                    $shippingData['calculated'] = $this->getCalculatedData();
                }
            }
        }

        if (!isset($shippingData['calculated'])) {

            if (($this->getShippingTemplate()->isLocalShippingFlatEnabled() &&
                 $this->getShippingTemplate()->isLocalShippingRateTableEnabled()) ||
                ($this->getShippingTemplate()->isInternationalShippingFlatEnabled() &&
                 $this->getShippingTemplate()->isInternationalShippingRateTableEnabled())) {

                $calculatedData = $this->getCalculatedData();
                unset($calculatedData['package_size']);
                unset($calculatedData['dimensions']);
                $shippingData['calculated'] = $calculatedData;
            }

            if ($this->isClickAndCollectAvailable() && $this->getShippingTemplate()->isClickAndCollectEnabled()) {
                $calculatedData = $this->getCalculatedData();
                unset($calculatedData['package_size']);
                $shippingData['calculated'] = $calculatedData;
            }
        }

        return $shippingData;
    }

    /**
     * @return array|null
     */
    public function getCalculatedData()
    {
        if (!is_null($this->calculatedShippingData)) {
            return $this->calculatedShippingData;
        }

        if (is_null($this->getCalculatedShippingTemplate())) {
            return array();
        }

        $data = array(
            'package_size' => $this->getCalculatedShippingSource()->getPackageSize(),
            'dimensions'   => $this->getCalculatedShippingSource()->getDimension(),
            'weight'       => $this->getCalculatedShippingSource()->getWeight()
        );

        switch ($this->getCalculatedShippingTemplate()->getMeasurementSystem()) {
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated::MEASUREMENT_SYSTEM_ENGLISH:
                $data['measurement_system'] = self::MEASUREMENT_SYSTEM_ENGLISH;
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated::MEASUREMENT_SYSTEM_METRIC:
                $data['measurement_system'] = self::MEASUREMENT_SYSTEM_METRIC;
                break;
        }

        return $this->calculatedShippingData = $data;
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getLocalShippingData()
    {
        $data = array(
            'type' => $this->getLocalType()
        );

        if ($this->getEbayMarketplace()->isInStorePickupEnabled() &&
            $this->getEbayAccount()->isPickupStoreEnabled()
        ) {
            $data['pickup_in_store_enabled'] = 1;
        }

        if ($this->getShippingTemplate()->isLocalShippingLocalEnabled() ||
            $this->getShippingTemplate()->isLocalShippingFreightEnabled()) {
            return $data;
        }

        $data['discount_enabled']    = $this->getShippingTemplate()->isLocalShippingDiscountEnabled();
        $data['discount_profile_id'] = $this->getShippingTemplate()
                                            ->getLocalShippingDiscountProfileId(
                                                $this->getListingProduct()->getListing()->getAccountId()
                                            );

        if ($this->getShippingTemplate()->isLocalShippingFlatEnabled()) {

            // there are permissions by marketplace (interface management)
            $data['rate_table_enabled'] = $this->getShippingTemplate()->isLocalShippingRateTableEnabled();

            if ($this->isClickAndCollectAvailable()) {
                $data['click_and_collect_enabled'] = $this->getShippingTemplate()->isClickAndCollectEnabled();
            }
        }

        if ($this->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {
            $data['handing_cost'] = $this->getCalculatedShippingTemplate()->getLocalHandlingCost();
        }

        $data['methods'] = $this->getLocalServices();

        return $data;
    }

    // ---------------------------------------

    private function getLocalType()
    {
        if ($this->getShippingTemplate()->isLocalShippingLocalEnabled()) {
            return self::SHIPPING_TYPE_LOCAL;
        }
        if ($this->getShippingTemplate()->isLocalShippingFreightEnabled()) {
            return self::SHIPPING_TYPE_FREIGHT;
        }
        if ($this->getShippingTemplate()->isLocalShippingFlatEnabled()) {
            return self::SHIPPING_TYPE_FLAT;
        }
        if ($this->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {
            return self::SHIPPING_TYPE_CALCULATED;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Unknown local shipping type.');
    }

    private function getLocalServices()
    {
        $services = array();

        foreach ($this->getShippingTemplate()->getServices(true) as $service) {

            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Shipping\Service */

            if (!$service->isShippingTypeLocal()) {
               continue;
            }

            $tempDataMethod = array(
                'service' => $service->getShippingValue()
            );

            if ($this->getShippingTemplate()->isLocalShippingFlatEnabled()) {

                $store = $this->getListing()->getStoreId();
                $tempDataMethod['cost'] = $service->getSource($this->getMagentoProduct())
                                                  ->getCost($store);

                $tempDataMethod['cost_additional'] = $service->getSource($this->getMagentoProduct())
                                                             ->getCostAdditional($store);

                if (!$this->getShippingTemplate()->isLocalShippingRateTableEnabled() &&
                    in_array($this->getShippingTemplate()->getMarketplaceId(), array(
                       \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_US,
                       \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_MOTORS,
                    )) && preg_match('/(FedEx|UPS)/', $service->getShippingValue())) {

                    $tempDataMethod['cost_surcharge'] = $service->getSource($this->getMagentoProduct())
                                                                ->getCostSurcharge($store);
                }
            }

            if ($this->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {
                $tempDataMethod['is_free'] = $service->isCostModeFree();
            }

            $services[] = $tempDataMethod;
        }

        return $services;
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getInternationalShippingData()
    {
        $data = array(
            'type' => $this->getInternationalType()
        );

        $data['discount_enabled'] = $this->getShippingTemplate()->isInternationalShippingDiscountEnabled();
        $data['discount_profile_id'] = $this->getShippingTemplate()
                                            ->getInternationalShippingDiscountProfileId(
                                                $this->getListingProduct()->getListing()->getAccountId()
                                            );

        if ($this->getShippingTemplate()->isInternationalShippingFlatEnabled()) {
            // there are permissions by marketplace (interface management)
            $data['rate_table_enabled'] = $this->getShippingTemplate()->isInternationalShippingRateTableEnabled();
        }

        if ($this->getShippingTemplate()->isInternationalShippingCalculatedEnabled()) {
            $data['handing_cost'] = $this->getCalculatedShippingTemplate()->getInternationalHandlingCost();
        }

        $data['methods'] = $this->getInternationalServices();

        return $data;
    }

    // ---------------------------------------

    private function getInternationalType()
    {
        if ($this->getShippingTemplate()->isInternationalShippingFlatEnabled()) {
            return self::SHIPPING_TYPE_FLAT;
        }

        if ($this->getShippingTemplate()->isInternationalShippingCalculatedEnabled()) {
            return self::SHIPPING_TYPE_CALCULATED;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Unknown international shipping type.');
    }

    private function getInternationalServices()
    {
        $services = array();

        foreach ($this->getShippingTemplate()->getServices(true) as $service) {

            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Shipping\Service */

            if (!$service->isShippingTypeInternational()) {
               continue;
            }

            $tempDataMethod = array(
                'service' => $service->getShippingValue(),
                'locations' => $service->getLocations()
            );

            if ($this->getShippingTemplate()->isInternationalShippingFlatEnabled()) {

                $store = $this->getListing()->getStoreId();
                $tempDataMethod['cost'] = $service->getSource($this->getMagentoProduct())
                                                  ->getCost($store);

                $tempDataMethod['cost_additional'] = $service->getSource($this->getMagentoProduct())
                                                             ->getCostAdditional($store);
            }

            $services[] = $tempDataMethod;
        }

        return $services;
    }

    //########################################

    private function isClickAndCollectAvailable()
    {
        if (!$this->getMarketplace()->getChildObject()->isClickAndCollectEnabled()) {
            return false;
        }

        if (!$this->getShippingTemplate()->isLocalShippingFlatEnabled() &&
            !$this->getShippingTemplate()->isLocalShippingCalculatedEnabled()
        ) {
            return false;
        }

        if ($this->getShippingTemplate()->getDispatchTime() > 3) {
            return false;
        }

        return true;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    private function getShippingTemplate()
    {
        if (is_null($this->shippingTemplate)) {
            $this->shippingTemplate = $this->getEbayListingProduct()->getShippingTemplate();
        }

        return $this->shippingTemplate;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Source | null
     */
    private function getShippingSource()
    {
        if (!is_null($shippingTemplate = $this->getShippingTemplate())) {
            return $shippingTemplate->getSource($this->getMagentoProduct());
        }

        return null;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated | null
     */
    private function getCalculatedShippingTemplate()
    {
        return $this->getShippingTemplate()->getCalculatedShipping();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated\Source | null
     */
    private function getCalculatedShippingSource()
    {
        if (!is_null($calculatedShipping = $this->getCalculatedShippingTemplate())) {
            return $calculatedShipping->getSource($this->getMagentoProduct());
        }

        return null;
    }

    //########################################
}