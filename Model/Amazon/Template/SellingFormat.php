<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Template\SellingFormat getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat getResource()
 */
namespace Ess\M2ePro\Model\Amazon\Template;

class SellingFormat extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    const QTY_MODIFICATION_MODE_OFF = 0;
    const QTY_MODIFICATION_MODE_ON = 1;

    const QTY_MIN_POSTED_DEFAULT_VALUE = 1;
    const QTY_MAX_POSTED_DEFAULT_VALUE = 100;

    const PRICE_VARIATION_MODE_PARENT   = 1;
    const PRICE_VARIATION_MODE_CHILDREN = 2;

    const DATE_VALUE      = 0;
    const DATE_ATTRIBUTE  = 1;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat');
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

        return (bool)$this->activeRecordFactory->getObject('Amazon\Listing')
            ->getCollection()
            ->addFieldToFilter('template_selling_format_id', $this->getId())
            ->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('template_sellingformat');
        return parent::save();
    }

    public function delete()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('template_sellingformat');
        return parent::delete();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getListings($asObjects = false, array $filters = array())
    {
        return $this->getRelatedComponentItems('Listing','template_selling_format_id',$asObjects,$filters);
    }

    //########################################

    /**
     * @return int
     */
    public function getQtyMode()
    {
        return (int)$this->getData('qty_mode');
    }

    /**
     * @return bool
     */
    public function isQtyModeProduct()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isQtyModeSingle()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_SINGLE;
    }

    /**
     * @return bool
     */
    public function isQtyModeNumber()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER;
    }

    /**
     * @return bool
     */
    public function isQtyModeAttribute()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE;
    }

    /**
     * @return bool
     */
    public function isQtyModeProductFixed()
    {
        return $this->getQtyMode() == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED;
    }

    /**
     * @return int
     */
    public function getQtyNumber()
    {
        return (int)$this->getData('qty_custom_value');
    }

    /**
     * @return array
     */
    public function getQtySource()
    {
        return array(
            'mode'      => $this->getQtyMode(),
            'value'     => $this->getQtyNumber(),
            'attribute' => $this->getData('qty_custom_attribute'),
            'qty_modification_mode'     => $this->getQtyModificationMode(),
            'qty_min_posted_value'      => $this->getQtyMinPostedValue(),
            'qty_max_posted_value'      => $this->getQtyMaxPostedValue(),
            'qty_percentage'            => $this->getQtyPercentage()
        );
    }

    /**
     * @return array
     */
    public function getQtyAttributes()
    {
        $attributes = array();
        $src = $this->getQtySource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getQtyPercentage()
    {
        return (int)$this->getData('qty_percentage');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getQtyModificationMode()
    {
        return (int)$this->getData('qty_modification_mode');
    }

    /**
     * @return bool
     */
    public function isQtyModificationModeOn()
    {
        return $this->getQtyModificationMode() == self::QTY_MODIFICATION_MODE_ON;
    }

    /**
     * @return bool
     */
    public function isQtyModificationModeOff()
    {
        return $this->getQtyModificationMode() == self::QTY_MODIFICATION_MODE_OFF;
    }

    /**
     * @return int
     */
    public function getQtyMinPostedValue()
    {
        return (int)$this->getData('qty_min_posted_value');
    }

    /**
     * @return int
     */
    public function getQtyMaxPostedValue()
    {
        return (int)$this->getData('qty_max_posted_value');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getPriceMode()
    {
        return (int)$this->getData('price_mode');
    }

    /**
     * @return bool
     */
    public function isPriceModeProduct()
    {
        return $this->getPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isPriceModeSpecial()
    {
        return $this->getPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isPriceModeAttribute()
    {
        return $this->getPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE;
    }

    public function getPriceCoefficient()
    {
        return $this->getData('price_coefficient');
    }

    /**
     * @return array
     */
    public function getPriceSource()
    {
        return array(
            'mode'        => $this->getPriceMode(),
            'coefficient' => $this->getPriceCoefficient(),
            'attribute'   => $this->getData('price_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getPriceAttributes()
    {
        $attributes = array();
        $src = $this->getPriceSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getMapPriceMode()
    {
        return (int)$this->getData('map_price_mode');
    }

    /**
     * @return bool
     */
    public function isMapPriceModeNone()
    {
        return $this->getMapPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_NONE;
    }

    /**
     * @return bool
     */
    public function isMapPriceModeProduct()
    {
        return $this->getMapPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isMapPriceModeSpecial()
    {
        return $this->getMapPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isMapPriceModeAttribute()
    {
        return $this->getMapPriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getMapPriceSource()
    {
        return array(
            'mode'        => $this->getMapPriceMode(),
            'attribute'   => $this->getData('map_price_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getMapPriceAttributes()
    {
        $attributes = array();
        $src = $this->getMapPriceSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSalePriceMode()
    {
        return (int)$this->getData('sale_price_mode');
    }

    /**
     * @return bool
     */
    public function isSalePriceModeNone()
    {
        return $this->getSalePriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_NONE;
    }

    /**
     * @return bool
     */
    public function isSalePriceModeProduct()
    {
        return $this->getSalePriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isSalePriceModeSpecial()
    {
        return $this->getSalePriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isSalePriceModeAttribute()
    {
        return $this->getSalePriceMode() == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE;
    }

    public function getSalePriceCoefficient()
    {
        return $this->getData('sale_price_coefficient');
    }

    /**
     * @return array
     */
    public function getSalePriceSource()
    {
        return array(
            'mode'        => $this->getSalePriceMode(),
            'coefficient' => $this->getSalePriceCoefficient(),
            'attribute'   => $this->getData('sale_price_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getSalePriceAttributes()
    {
        $attributes = array();
        $src = $this->getSalePriceSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSalePriceStartDateMode()
    {
        return (int)$this->getData('sale_price_start_date_mode');
    }

    /**
     * @return bool
     */
    public function isSalePriceStartDateModeValue()
    {
        return $this->getSalePriceStartDateMode() == self::DATE_VALUE;
    }

    /**
     * @return bool
     */
    public function isSalePriceStartDateModeAttribute()
    {
        return $this->getSalePriceStartDateMode() == self::DATE_ATTRIBUTE;
    }

    public function getSalePriceStartDateValue()
    {
        return $this->getData('sale_price_start_date_value');
    }

    /**
     * @return array
     */
    public function getSalePriceStartDateSource()
    {
        return array(
            'mode'        => $this->getSalePriceStartDateMode(),
            'value'       => $this->getSalePriceStartDateValue(),
            'attribute'   => $this->getData('sale_price_start_date_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getSalePriceStartDateAttributes()
    {
        $attributes = array();
        $src = $this->getSalePriceStartDateSource();

        if ($src['mode'] == self::DATE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSalePriceEndDateMode()
    {
        return (int)$this->getData('sale_price_end_date_mode');
    }

    /**
     * @return bool
     */
    public function isSalePriceEndDateModeValue()
    {
        return $this->getSalePriceEndDateMode() == self::DATE_VALUE;
    }

    /**
     * @return bool
     */
    public function isSalePriceEndDateModeAttribute()
    {
        return $this->getSalePriceEndDateMode() == self::DATE_ATTRIBUTE;
    }

    public function getSalePriceEndDateValue()
    {
        return $this->getData('sale_price_end_date_value');
    }

    /**
     * @return array
     */
    public function getSalePriceEndDateSource()
    {
        return array(
            'mode'        => $this->getSalePriceEndDateMode(),
            'value'       => $this->getSalePriceEndDateValue(),
            'attribute'   => $this->getData('sale_price_end_date_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getSalePriceEndDateAttributes()
    {
        $attributes = array();
        $src = $this->getSalePriceEndDateSource();

        if ($src['mode'] == self::DATE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function usesProductOrSpecialPrice()
    {
        if ($this->isPriceModeProduct() || $this->isPriceModeSpecial()) {
            return true;
        }

        if ($this->isSalePriceModeProduct() || $this->isSalePriceModeSpecial()) {
            return true;
        }

        return false;
    }

    //########################################

    /**
     * @return int
     */
    public function getPriceVariationMode()
    {
        return (int)$this->getData('price_variation_mode');
    }

    /**
     * @return bool
     */
    public function isPriceVariationModeParent()
    {
        return $this->getPriceVariationMode() == self::PRICE_VARIATION_MODE_PARENT;
    }

    /**
     * @return bool
     */
    public function isPriceVariationModeChildren()
    {
        return $this->getPriceVariationMode() == self::PRICE_VARIATION_MODE_CHILDREN;
    }

    //########################################

    /**
     * @return float
     */
    public function getPriceVatPercent()
    {
        return (float)$this->getData('price_vat_percent');
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        return array_unique(array_merge(
            $this->getQtyAttributes(),
            $this->getPriceAttributes(),
            $this->getMapPriceAttributes(),
            $this->getSalePriceAttributes(),
            $this->getSalePriceStartDateAttributes(),
            $this->getSalePriceEndDateAttributes()
        ));
    }

    //########################################

    /**
     * @param bool $asArrays
     * @param string|array $columns
     * @param bool $onlyPhysicalUnits
     * @return array
     */
    public function getAffectedListingsProducts($asArrays = true, $columns = '*', $onlyPhysicalUnits = false)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $listingCollection */
        $listingCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing'
        )->getCollection();
        $listingCollection->addFieldToFilter('template_selling_format_id', $this->getId());
        $listingCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingCollection->getSelect()->columns('id');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product'
        )->getCollection();
        $listingProductCollection->addFieldToFilter('listing_id',array('in' => $listingCollection->getSelect()));

        if ($onlyPhysicalUnits) {
            $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        }

        if (is_array($columns) && !empty($columns)) {
            $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $listingProductCollection->getSelect()->columns($columns);
        }

        return $asArrays ? (array)$listingProductCollection->getData() : (array)$listingProductCollection->getItems();
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $listingsProducts = $this->getAffectedListingsProducts(true, array('id'), true);
        if (empty($listingsProducts)) {
            return;
        }

        $this->getResource()->setSynchStatusNeed($newData,$oldData,$listingsProducts);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }
    
    //########################################
}