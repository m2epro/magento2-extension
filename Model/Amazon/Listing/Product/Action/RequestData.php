<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

class RequestData extends \Ess\M2ePro\Model\Listing\Product\Action\RequestData
{
    public const MAP_PRICE = 'map_price';

    /**
     * @return bool
     */
    public function hasSku()
    {
        return isset($this->getData()['sku']);
    }

    /**
     * @return bool
     */
    public function hasProductId()
    {
        return isset($this->getData()['product_id']);
    }

    /**
     * @return bool
     */
    public function hasProductIdType()
    {
        return isset($this->getData()['product_id_type']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasTypeMode()
    {
        return isset($this->getData()['type_mode']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasQty()
    {
        return isset($this->getData()['qty']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasHandlingTime()
    {
        return isset($this->getData()['handling_time']);
    }

    /**
     * @return bool
     */
    public function hasRestockDate()
    {
        return isset($this->getData()['restock_date']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasRegularPrice()
    {
        return isset($this->getData()['price']);
    }

    /**
     * @return bool
     */
    public function hasRegularSalePrice()
    {
        return isset($this->getData()['sale_price']);
    }

    // ---------------------------------------

    public function hasBusinessPrice()
    {
        return isset($this->getData()['business_price']);
    }

    public function hasBusinessDiscounts()
    {
        return isset($this->getData()['business_discounts']);
    }

    // ---------------------------------------

    public function hasDeleteBusinessPriceFlag()
    {
        return isset($this->getData()['delete_business_price_flag']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasCondition()
    {
        return isset($this->getData()['condition']);
    }

    /**
     * @return bool
     */
    public function hasConditionNote()
    {
        return isset($this->getData()['condition_note']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasGiftWrap()
    {
        return isset($this->getData()['gift_wrap']);
    }

    /**
     * @return bool
     */
    public function hasGiftMessage()
    {
        return isset($this->getData()['gift_message']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasShippingData()
    {
        return isset($this->getData()['shipping_data']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasTaxCode()
    {
        return isset($this->getData()['tax_code']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasNumberOfItems()
    {
        return isset($this->getData()['number_of_items']);
    }

    /**
     * @return bool
     */
    public function hasItemPackageQuantity()
    {
        return isset($this->getData()['item_package_quantity']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasBrowsenodeId()
    {
        return isset($this->getData()['browsenode_id']);
    }

    /**
     * @return bool
     */
    public function hasProductTypeNick()
    {
        return isset($this->getData()['product_type_nick']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasProductData()
    {
        return isset($this->getData()['product_data']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasVariationAttributes()
    {
        return isset($this->getData()['variation_data']['attributes']);
    }

    //########################################

    /**
     * @return string|null
     */
    public function getSku()
    {
        return $this->hasSku() ? $this->getData('sku') : null;
    }

    /**
     * @return int|null
     */
    public function getProductId()
    {
        return $this->hasProductId() ? $this->getData('product_id') : null;
    }

    /**
     * @return string|null
     */
    public function getProductIdType()
    {
        return $this->hasProductIdType() ? $this->getData('product_id_type') : null;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isTypeModeExist()
    {
        if (!$this->hasTypeMode()) {
            return false;
        }

        return $this->getData('type_mode')
            == \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request::LIST_TYPE_EXIST;
    }

    /**
     * @return bool
     */
    public function isTypeModeNew()
    {
        if (!$this->hasTypeMode()) {
            return false;
        }

        $listTypeNew = \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request::LIST_TYPE_NEW;

        return $this->getData('type_mode') == $listTypeNew;
    }

    // ---------------------------------------

    /**
     * @return int|null
     */
    public function getQty()
    {
        return $this->hasQty() ? $this->getData('qty') : null;
    }

    // ---------------------------------------

    /**
     * @return string|null
     */
    public function getHandlingTime()
    {
        return $this->hasHandlingTime() ? $this->getData('handling_time') : null;
    }

    /**
     * @return bool|null
     */
    public function getRestockDate()
    {
        return $this->hasRestockDate() ? $this->getData('restock_date') : null;
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getRegularPrice()
    {
        return $this->hasRegularPrice() ? $this->getData('price') : null;
    }

    /**
     * @return float|null
     */
    public function getRegularSalePrice()
    {
        return $this->hasRegularSalePrice() ? $this->getData('sale_price') : null;
    }

    /**
     * @return string|null
     */
    public function getRegularSalePriceStartDate()
    {
        return $this->hasRegularSalePrice() ? $this->getData('sale_price_start_date') : null;
    }

    /**
     * @return string|null
     */
    public function getRegularSalePriceEndDate()
    {
        return $this->hasRegularSalePrice() ? $this->getData('sale_price_end_date') : null;
    }

    public function getMapPrice(): float
    {
        $mapPrice = $this->getData()[self::MAP_PRICE] ?? null;
        if (empty($mapPrice)) {
            return 0.0;
        }

        return (float)$mapPrice;
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getBusinessPrice()
    {
        return $this->hasBusinessPrice() ? $this->getData('business_price') : null;
    }

    /**
     * @return array|null
     */
    public function getBusinessDiscounts()
    {
        return $this->hasBusinessDiscounts() ? $this->getData('business_discounts') : null;
    }

    // ---------------------------------------

    /**
     * @return string|null
     */
    public function getCondition()
    {
        return $this->hasCondition() ? $this->getData('condition') : null;
    }

    /**
     * @return string|null
     */
    public function getConditionNote()
    {
        return $this->hasConditionNote() ? $this->getData('condition_note') : null;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function getGiftWrap()
    {
        return $this->hasGiftWrap() ? $this->getData()['gift_wrap'] : null;
    }

    /**
     * @return bool
     */
    public function getGiftMessage()
    {
        return $this->hasGiftMessage() ? $this->getData()['gift_message'] : null;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function getShippingData()
    {
        return $this->hasShippingData() ? $this->getData()['shipping_data'] : null;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function getTaxCode()
    {
        return $this->hasTaxCode() ? $this->getData()['tax_code'] : null;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function getNumberOfItems()
    {
        return $this->hasNumberOfItems() ? $this->getData()['number_of_items'] : null;
    }

    /**
     * @return bool
     */
    public function getItemPackageQuantity()
    {
        return $this->hasItemPackageQuantity() ? $this->getData()['item_package_quantity'] : null;
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getBrowsenodeId()
    {
        return $this->hasBrowsenodeId() ? $this->getData('browsenode_id') : null;
    }

    /**
     * @return string|null
     */
    public function getProductTypeNick()
    {
        return $this->hasProductTypeNick() ? $this->getData('product_type_nick') : null;
    }

    // ---------------------------------------

    /**
     * @return string|null
     */
    public function getProductData()
    {
        return $this->hasProductData() ? $this->getData('product_data') : null;
    }

    // ---------------------------------------

    /**
     * @return mixed
     */
    public function getVariationAttributes()
    {
        return $this->hasVariationAttributes() ? $this->getData()['variation_data']['attributes'] : null;
    }

    //########################################
}
