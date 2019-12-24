<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\RequestData
 */
class RequestData extends \Ess\M2ePro\Model\AbstractModel
{
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
    public function hasIsNeedSkuUpdate()
    {
        return isset($this->getData()['is_need_sku_update']);
    }

    /**
     * @return bool
     */
    public function hasProductIdsData()
    {
        return isset($this->getData()['product_ids_data']);
    }

    public function hasIsNeedProductIdUpdate()
    {
        return isset($this->getData()['is_need_product_id_update']);
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
    public function hasLagTime()
    {
        return isset($this->getData()['lag_time']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasPrice()
    {
        return isset($this->getData()['price']);
    }

    /**
     * @return bool
     */
    public function hasPromotionPrices()
    {
        return isset($this->getData()['promotion_prices']);
    }

    // ---------------------------------------

    public function hasVariationData()
    {
        return isset($this->getData()['variation_data']);
    }

    //########################################

    /**
     * @return string|null
     */
    public function getSku()
    {
        return $this->hasSku() ? $this->getData('sku') : null;
    }

    public function getIsNeedSkuUpdate()
    {
        return $this->hasIsNeedSkuUpdate() ? $this->getData('is_need_sku_update') : null;
    }

    /**
     * @return int|null
     */
    public function getProductIdsData()
    {
        return $this->hasProductIdsData() ? $this->getData('product_ids_data') : null;
    }

    public function getIsNeedProductIdUpdate()
    {
        return $this->hasIsNeedProductIdUpdate() ? $this->getData('is_need_product_id_update') : null;
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
    public function getLagTime()
    {
        return $this->hasLagTime() ? $this->getData('lag_time') : null;
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getPrice()
    {
        return $this->hasPrice() ? $this->getData('price') : null;
    }

    // ---------------------------------------

    public function getVariationData()
    {
        return $this->hasVariationData() ? $this->getData('variation_data') : null;
    }

    //########################################
}
