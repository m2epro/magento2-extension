<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping getResource()
 */

namespace Ess\M2ePro\Model\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Shipping
 */
class Shipping extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const COUNTRY_MODE_CUSTOM_VALUE     = 1;
    const COUNTRY_MODE_CUSTOM_ATTRIBUTE = 2;

    const POSTAL_CODE_MODE_NONE             = 0;
    const POSTAL_CODE_MODE_CUSTOM_VALUE     = 1;
    const POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE = 2;

    const ADDRESS_MODE_NONE             = 0;
    const ADDRESS_MODE_CUSTOM_VALUE     = 1;
    const ADDRESS_MODE_CUSTOM_ATTRIBUTE = 2;

    const SHIPPING_TYPE_FLAT             = 0;
    const SHIPPING_TYPE_CALCULATED       = 1;
    const SHIPPING_TYPE_FREIGHT          = 2;
    const SHIPPING_TYPE_LOCAL            = 3;
    const SHIPPING_TYPE_NO_INTERNATIONAL = 4;

    const DISPATCH_TIME_MODE_VALUE     = 1;
    const DISPATCH_TIME_MODE_ATTRIBUTE = 2;

    const SHIPPING_RATE_TABLE_ACCEPT_MODE     = 1;
    const SHIPPING_RATE_TABLE_IDENTIFIER_MODE = 2;

    const CROSS_BORDER_TRADE_NONE           = 0;
    const CROSS_BORDER_TRADE_NORTH_AMERICA  = 1;
    const CROSS_BORDER_TRADE_UNITED_KINGDOM = 2;

    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated
     */
    private $calculatedShippingModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Source[]
     */
    private $shippingSourceModels = [];

    protected $ebayFactory;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping');
    }

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    /**
     * @return string
     */
    public function getNick()
    {
        return \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return (bool)$this->activeRecordFactory->getObject('Ebay\Listing')
                ->getCollection()
                ->addFieldToFilter('template_shipping_id', $this->getId())
                ->getSize() ||
            (bool)$this->activeRecordFactory->getObject('Ebay_Listing_Product')
                ->getCollection()
                ->addFieldToFilter(
                    'template_shipping_mode',
                    \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE
                )
                ->addFieldToFilter('template_shipping_id', $this->getId())
                ->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_shipping');

        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $calculatedShippingObject = $this->getCalculatedShipping();
        if ($calculatedShippingObject !== null) {
            $calculatedShippingObject->delete();
        }

        $services = $this->getServices(true);
        foreach ($services as $service) {
            $service->delete();
        }

        $this->marketplaceModel        = null;
        $this->calculatedShippingModel = null;
        $this->shippingSourceModels    = [];

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_shipping');

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if ($this->marketplaceModel === null) {
            $this->marketplaceModel = $this->ebayFactory->getCachedObjectLoaded(
                'Marketplace',
                $this->getMarketplaceId()
            );
        }

        return $this->marketplaceModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $instance
     */
    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $instance)
    {
        $this->marketplaceModel = $instance;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->shippingSourceModels[$productId])) {
            return $this->shippingSourceModels[$productId];
        }

        $this->shippingSourceModels[$productId] = $this->modelFactory->getObject('Ebay_Template_Shipping_Source');
        $this->shippingSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->shippingSourceModels[$productId]->setShippingTemplate($this);

        return $this->shippingSourceModels[$productId];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated
     */
    public function getCalculatedShipping()
    {
        if ($this->calculatedShippingModel === null) {
            try {
                $this->calculatedShippingModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_Shipping_Calculated',
                    $this->getId()
                );

                $this->calculatedShippingModel->setShippingTemplate($this);
            } catch (\Exception $exception) {
                return $this->calculatedShippingModel;
            }
        }

        return $this->calculatedShippingModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated $instance
     */
    public function setCalculatedShipping(\Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated $instance)
    {
        $this->calculatedShippingModel = $instance;
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @param array $sort
     * @return array|\Ess\M2ePro\Model\Ebay\Template\Shipping\Service[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getServices(
        $asObjects = false,
        array $filters = [],
        array $sort = ['priority' => \Magento\Framework\Data\Collection::SORT_ORDER_ASC]
    ) {
        $services = $this->getRelatedSimpleItems(
            'Ebay_Template_Shipping_Service',
            'template_shipping_id',
            $asObjects,
            $filters,
            $sort
        );

        if ($asObjects) {
            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Shipping\Service */
            foreach ($services as $service) {
                $service->setShippingTemplate($this);
            }
        }

        return $services;
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    /**
     * @return bool
     */
    public function isCustomTemplate()
    {
        return (bool)$this->getData('is_custom_template');
    }

    /**
     * @return int
     */
    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    // ---------------------------------------

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    //########################################

    /**
     * @return int
     */
    public function getCountryMode()
    {
        return (int)$this->getData('country_mode');
    }

    public function getCountryCustomValue()
    {
        return $this->getData('country_custom_value');
    }

    public function getCountryCustomAttribute()
    {
        return $this->getData('country_custom_attribute');
    }

    /**
     * @return array
     */
    public function getCountrySource()
    {
        return [
            'mode'      => $this->getCountryMode(),
            'value'     => $this->getCountryCustomValue(),
            'attribute' => $this->getCountryCustomAttribute()
        ];
    }

    /**
     * @return array
     */
    public function getCountryAttributes()
    {
        $attributes = [];
        $src        = $this->getCountrySource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping::COUNTRY_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getPostalCodeMode()
    {
        return (int)$this->getData('postal_code_mode');
    }

    public function getPostalCodeCustomValue()
    {
        return $this->getData('postal_code_custom_value');
    }

    public function getPostalCodeCustomAttribute()
    {
        return $this->getData('postal_code_custom_attribute');
    }

    /**
     * @return array
     */
    public function getPostalCodeSource()
    {
        return [
            'mode'      => $this->getPostalCodeMode(),
            'value'     => $this->getPostalCodeCustomValue(),
            'attribute' => $this->getPostalCodeCustomAttribute()
        ];
    }

    /**
     * @return array
     */
    public function getPostalCodeAttributes()
    {
        $attributes = [];
        $src        = $this->getPostalCodeSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping::POSTAL_CODE_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getAddressMode()
    {
        return (int)$this->getData('address_mode');
    }

    public function getAddressCustomValue()
    {
        return $this->getData('address_custom_value');
    }

    public function getAddressCustomAttribute()
    {
        return $this->getData('address_custom_attribute');
    }

    /**
     * @return array
     */
    public function getAddressSource()
    {
        return [
            'mode'      => $this->getAddressMode(),
            'value'     => $this->getAddressCustomValue(),
            'attribute' => $this->getAddressCustomAttribute()
        ];
    }

    /**
     * @return array
     */
    public function getAddressAttributes()
    {
        $attributes = [];
        $src        = $this->getAddressSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping::ADDRESS_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isGlobalShippingProgramEnabled()
    {
        return (bool)$this->getData('global_shipping_program');
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return bool
     */
    public function isLocalShippingRateTableEnabled(\Ess\M2ePro\Model\Account $account)
    {
        try {
            $rateTable = $this->getRateTable('local', $account);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            return null;
        }

        return !empty($rateTable['value']) ? (bool)$rateTable['value'] : null;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return int
     */
    public function getLocalShippingRateTableMode(\Ess\M2ePro\Model\Account $account)
    {
        $rateTable = $this->getLocalShippingRateTable($account);

        return $rateTable['mode'];
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return mixed
     */
    public function getLocalShippingRateTableId(\Ess\M2ePro\Model\Account $account)
    {
        $rateTable = $this->getLocalShippingRateTable($account);

        return $rateTable['value'];
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return bool
     */
    public function getLocalShippingRateTable(\Ess\M2ePro\Model\Account $account)
    {
        return $this->getRateTable('local', $account);
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return bool
     */
    public function isInternationalShippingRateTableEnabled(\Ess\M2ePro\Model\Account $account)
    {
        try {
            $rateTable = $this->getRateTable('international', $account);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            return false;
        }

        return !empty($rateTable['value']) ? (bool)$rateTable['value'] : null;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return int
     */
    public function getInternationalShippingRateTableMode(\Ess\M2ePro\Model\Account $account)
    {
        $rateTable = $this->getInternationalShippingRateTable($account);

        return $rateTable['mode'];
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return mixed
     */
    public function getInternationalShippingRateTableId(\Ess\M2ePro\Model\Account $account)
    {
        $rateTable = $this->getInternationalShippingRateTable($account);

        return $rateTable['value'];
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return bool
     */
    public function getInternationalShippingRateTable(\Ess\M2ePro\Model\Account $account)
    {
        return $this->getRateTable('international', $account);
    }

    /**
     * @param $type
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getRateTable($type, \Ess\M2ePro\Model\Account $account)
    {
        $rateTables = $this->getSettings($type . '_shipping_rate_table');

        foreach ($rateTables as $accountId => $rateTableData) {
            if ($account->getId() == $accountId) {
                return $rateTableData;
            }
        }

        throw new \Ess\M2ePro\Model\Exception\Logic(
            $this->getHelper('Module\Translation')->__(
                'Domestic or International Shipping Rate Table data is not found for this account. 
                Make sure to <a href="%url%" target="_blank">download Rate Tables from eBay</a> 
                in the M2E Pro Shipping Policy.',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl(
                    "x/z4R8AQ#eBayGuaranteedDelivery-HowtodownloadeBayShippingRateTablestoM2EPro?"
                )
            )
        );
    }

    //########################################

    /**
     * @return int
     */
    public function getDispatchTimeMode()
    {
        return (int)$this->getData('dispatch_time_mode');
    }

    public function getDispatchTimeValue()
    {
        return $this->getData('dispatch_time_value');
    }

    public function getDispatchTimeAttribute()
    {
        return $this->getData('dispatch_time_attribute');
    }

    /**
     * @return array
     */
    public function getDispatchTimeSource()
    {
        return [
            'mode'      => $this->getDispatchTimeMode(),
            'value'     => $this->getDispatchTimeValue(),
            'attribute' => $this->getDispatchTimeAttribute()
        ];
    }

    /**
     * @return array
     */
    public function getDispatchTimeAttributes()
    {
        $attributes = [];
        $src        = $this->getDispatchTimeSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Shipping::DISPATCH_TIME_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    //########################################

    /**
     * @return bool
     */
    public function isLocalShippingFlatEnabled()
    {
        return (int)$this->getData('local_shipping_mode') == self::SHIPPING_TYPE_FLAT;
    }

    /**
     * @return bool
     */
    public function isLocalShippingCalculatedEnabled()
    {
        return (int)$this->getData('local_shipping_mode') == self::SHIPPING_TYPE_CALCULATED;
    }

    /**
     * @return bool
     */
    public function isLocalShippingFreightEnabled()
    {
        return (int)$this->getData('local_shipping_mode') == self::SHIPPING_TYPE_FREIGHT;
    }

    /**
     * @return bool
     */
    public function isLocalShippingLocalEnabled()
    {
        return (int)$this->getData('local_shipping_mode') == self::SHIPPING_TYPE_LOCAL;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isLocalShippingDiscountPromotionalEnabled()
    {
        return (bool)$this->getData('local_shipping_discount_promotional_mode');
    }

    public function getLocalShippingDiscountCombinedProfileId($accountId)
    {
        $data = $this->getData('local_shipping_discount_combined_profile_id');

        if ($data === null) {
            return null;
        }

        $data = $this->getHelper('Data')->jsonDecode($data);

        return !isset($data[$accountId]) ? null : $data[$accountId];
    }

    //########################################

    /**
     * @return bool
     */
    public function isInternationalShippingNoInternationalEnabled()
    {
        return (int)$this->getData('international_shipping_mode') == self::SHIPPING_TYPE_NO_INTERNATIONAL;
    }

    /**
     * @return bool
     */
    public function isInternationalShippingFlatEnabled()
    {
        return (int)$this->getData('international_shipping_mode') == self::SHIPPING_TYPE_FLAT;
    }

    /**
     * @return bool
     */
    public function isInternationalShippingCalculatedEnabled()
    {
        return (int)$this->getData('international_shipping_mode') == self::SHIPPING_TYPE_CALCULATED;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isInternationalShippingDiscountPromotionalEnabled()
    {
        return (bool)$this->getData('international_shipping_discount_promotional_mode');
    }

    public function getInternationalShippingDiscountCombinedProfileId($accountId)
    {
        $data = $this->getData('international_shipping_discount_combined_profile_id');

        if ($data === null) {
            return null;
        }

        $data = $this->getHelper('Data')->jsonDecode($data);

        return !isset($data[$accountId]) ? null : $data[$accountId];
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getExcludedLocations()
    {
        $excludedLocations = $this->getData('excluded_locations');
        is_string($excludedLocations) && $excludedLocations = $this->getHelper('Data')->jsonDecode($excludedLocations);

        return is_array($excludedLocations) ? $excludedLocations : [];
    }

    /**
     * @return float|null
     */
    public function getCashOnDeliveryCost()
    {
        $tempData = $this->getData('cash_on_delivery_cost');

        if (!empty($tempData)) {
            return (float)$tempData;
        }

        return null;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getCrossBorderTrade()
    {
        return (int)$this->getData('cross_border_trade');
    }

    /**
     * @return bool
     */
    public function isCrossBorderTradeNone()
    {
        return $this->getCrossBorderTrade() == self::CROSS_BORDER_TRADE_NONE;
    }

    /**
     * @return bool
     */
    public function isCrossBorderTradeNorthAmerica()
    {
        return $this->getCrossBorderTrade() == self::CROSS_BORDER_TRADE_NORTH_AMERICA;
    }

    /**
     * @return bool
     */
    public function isCrossBorderTradeUnitedKingdom()
    {
        return $this->getCrossBorderTrade() == self::CROSS_BORDER_TRADE_UNITED_KINGDOM;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Service[]
     */
    public function getLocalShippingServices()
    {
        $returns = [];

        $services = $this->getServices(true);
        foreach ($services as $service) {

            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Shipping\Service */

            if ($service->isShippingTypeLocal()) {
                $returns[] = $service;
            }
        }

        return $returns;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Service[]
     */
    public function getInternationalShippingServices()
    {
        $returns = [];

        $services = $this->getServices(true);
        foreach ($services as $service) {

            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Shipping\Service */

            if ($service->isShippingTypeInternational()) {
                $returns[] = $service;
            }
        }

        return $returns;
    }

    //########################################

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
