<?php

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Condition;

class Product extends \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Product
{
    private \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Backend\Model\UrlInterface $url,
        \Magento\Eav\Model\Config $config,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Rule\Model\Condition\Context $context,
        array $data = []
    ) {
        parent::__construct(
            $url,
            $config,
            $attrSetCollection,
            $productFactory,
            $localeFormat,
            $helperData,
            $modelFactory,
            $helperFactory,
            $context,
            $data
        );

        $this->localeDate = $localeDate;
    }

    /**
     * @return array
     */
    protected function getCustomFilters()
    {
        $ebayFilters = [
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
        ];

        return array_merge_recursive(
            parent::getCustomFilters(),
            $ebayFilters
        );
    }

    /**
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
            'Ebay\Magento\Product\Rule\Custom\\' . $customFilters[$filterId],
            [
                'filterOperator' => $this->getData('operator'),
                'filterCondition' => $this->getData('value'),
            ]
        );

        $isReadyToCache && $this->_customFiltersCache[$filterId] = $model;

        return $model;
    }

    /**
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

    public function getValueParsed()
    {
        /**
         * @see parent::validateAttribute()
         * @see \Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom\EbayStartDate::getValueByProductInstance()
         * @see \Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom\EbayEndDate::getValueByProductInstance()
         */
        if (
            $this->isAttribute('ebay_start_date')
            || $this->isAttribute('ebay_end_date')
        ) {
            return $this->getTimestampValueParsed();
        }

        return parent::getValueParsed();
    }

    private function isAttribute(string $attribute): bool
    {
        return $this->getData('attribute') === $attribute;
    }

    private function getTimestampValueParsed(): int
    {
        if ($this->getData('value_parsed') !== null) {
            return $this->getData('value_parsed');
        }

        $date = $this->localeDate->formatDate(
            $this->getData('value'),
            \IntlDateFormatter::MEDIUM,
            true
        );

        $timestamp = (int)\Ess\M2ePro\Helper\Date::createDateGmt($date)
                                                 ->format('U');

        $this->setData('value_parsed', $timestamp);

        return $timestamp;
    }
}
