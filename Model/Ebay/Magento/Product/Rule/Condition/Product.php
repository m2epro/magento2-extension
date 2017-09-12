<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Condition;

class Product extends \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Product
{
    //########################################

    /**
     * @return array
     */
    protected function getCustomFilters()
    {
        $ebayFilters = array(
            'ebay_status' => 'EbayStatus',
            'ebay_item_id' => 'EbayItemId',
            'ebay_available_qty' => 'EbayAvailableQty',
            'ebay_sold_qty' => 'EbaySoldQty',
            'ebay_online_current_price' => 'EbayPrice',
            'ebay_online_start_price' => 'EbayStartPrice',
            'ebay_online_reserve_price' => 'EbayReservePrice',
            'ebay_online_buyitnow_price' => 'EbayBuyItNowPrice',
            'ebay_online_title' => 'EbayTitle',
            'ebay_online_sku' => 'EbaySku',
            'ebay_online_category_id' => 'EbayCategoryId',
            'ebay_online_category_path' => 'EbayCategoryPath',
            'ebay_start_date' => 'EbayStartDate',
            'ebay_end_date' => 'EbayEndDate',
        );

        return array_merge_recursive(
            parent::getCustomFilters(),
            $ebayFilters
        );
    }

    /**
     * @param $filterId
     * @return \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
     */
    protected function getCustomFilterInstance($filterId, $isReadyToCache = true)
    {
        $parentFilters = parent::getCustomFilters();
        if (isset($parentFilters[$filterId])) {
            return parent::getCustomFilterInstance($filterId, $isReadyToCache);
        }

        $customFilters = $this->getCustomFilters();
        if (!isset($customFilters[$filterId])) {
            return null;
        }

        if (isset($this->_customFiltersCache[$filterId])) {
            return $this->_customFiltersCache[$filterId];
        }

        $model = $this->modelFactory->getObject(
            'Ebay\Magento\Product\Rule\Custom\\'.$customFilters[$filterId], [
                'filterOperator'  => $this->getData('operator'),
                'filterCondition' => $this->getData('value')
            ]
        );

        $isReadyToCache && $this->_customFiltersCache[$filterId] = $model;
        return $model;
    }

    //########################################

    /**
     * @param mixed $validatedValue
     * @return bool
     */
    public function validateAttribute($validatedValue)
    {
        if (is_array($validatedValue)) {
            $result = false;

            foreach ($validatedValue as $value) {
                $result = parent::validateAttribute($value);
                if ($result) {
                    break;
                }
            }

            return $result;
        }

        return parent::validateAttribute($validatedValue);
    }

    //########################################
}