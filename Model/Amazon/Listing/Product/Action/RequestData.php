<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

class RequestData extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = NULL;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $object
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $object)
    {
        $this->listingProduct = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    //########################################

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
    public function hasBrowsenodeId()
    {
        return isset($this->getData()['browsenode_id']);
    }

    /**
     * @return bool
     */
    public function hasProductDataNick()
    {
        return isset($this->getData()['product_data_nick']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasProductData()
    {
        return isset($this->getData()['product_data']);
    }

    /**
     * @return bool
     */
    public function hasDescriptionData()
    {
        return isset($this->getData()['description_data']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasImagesData()
    {
        return isset($this->getData()['images_data']);
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
        return $this->hasSku() ? $this->getData('sku') : NULL;
    }

    /**
     * @return int|null
     */
    public function getProductId()
    {
        return $this->hasProductId() ? $this->getData('product_id') : NULL;
    }

    /**
     * @return string|null
     */
    public function getProductIdType()
    {
        return $this->hasProductIdType() ? $this->getData('product_id_type') : NULL;
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
        return $this->hasQty() ? $this->getData('qty') : NULL;
    }

    // ---------------------------------------

    /**
     * @return string|null
     */
    public function getHandlingTime()
    {
        return $this->hasHandlingTime() ? $this->getData('handling_time') : NULL;
    }

    /**
     * @return bool|null
     */
    public function getRestockDate()
    {
        return $this->hasRestockDate() ? $this->getData('restock_date') : NULL;
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getRegularPrice()
    {
        return $this->hasRegularPrice() ? $this->getData('price') : NULL;
    }

    /**
     * @return float|null
     */
    public function getRegularSalePrice()
    {
        return $this->hasRegularSalePrice() ? $this->getData('sale_price') : NULL;
    }

    /**
     * @return string|null
     */
    public function getRegularSalePriceStartDate()
    {
        return $this->hasRegularSalePrice() ? $this->getData('sale_price_start_date') : NULL;
    }

    /**
     * @return string|null
     */
    public function getRegularSalePriceEndDate()
    {
        return $this->hasRegularSalePrice() ? $this->getData('sale_price_end_date') : NULL;
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getBusinessPrice()
    {
        return $this->hasBusinessPrice() ? $this->getData('business_price') : NULL;
    }

    /**
     * @return array|null
     */
    public function getBusinessDiscounts()
    {
        return $this->hasBusinessDiscounts() ? $this->getData('business_discounts') : NULL;
    }

    // ---------------------------------------

    /**
     * @return string|null
     */
    public function getCondition()
    {
        return $this->hasCondition() ? $this->getData('condition') : NULL;
    }

    /**
     * @return string|null
     */
    public function getConditionNote()
    {
        return $this->hasConditionNote() ? $this->getData('condition_note') : NULL;
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getBrowsenodeId()
    {
        return $this->hasBrowsenodeId() ? $this->getData('browsenode_id') : NULL;
    }

    /**
     * @return string|null
     */
    public function getProductDataNick()
    {
        return $this->hasProductDataNick() ? $this->getData('product_data_nick') : NULL;
    }

    // ---------------------------------------

    /**
     * @return string|null
     */
    public function getProductData()
    {
        return $this->hasProductData() ? $this->getData('product_data') : NULL;
    }

    /**
     * @return string|null
     */
    public function getDescriptionData()
    {
        return $this->hasDescriptionData() ? $this->getData('description_data') : NULL;
    }

    // ---------------------------------------

    /**
     * @return mixed
     */
    public function getImagesData()
    {
        return $this->hasImagesData() ? $this->getData('images_data') : NULL;
    }

    // ---------------------------------------

    /**
     * @return mixed
     */
    public function getVariationAttributes()
    {
        return $this->hasVariationAttributes() ? $this->getData()['variation_data']['attributes'] : NULL;
    }

    //########################################
}