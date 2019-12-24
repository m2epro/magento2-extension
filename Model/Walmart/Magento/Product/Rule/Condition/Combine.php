<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Magento\Product\Rule\Condition;

/**
 * Class \Ess\M2ePro\Model\Walmart\Magento\Product\Rule\Condition\Combine
 */
class Combine extends \Ess\M2ePro\Model\Magento\Product\Rule\Condition\Combine
{
    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Rule\Model\Condition\Context $context,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $context, $data);
        $this->setType('Walmart\Magento\Product\Rule\Condition\Combine');
    }

    //########################################

    protected function getConditionCombine()
    {
        return $this->getType() . '|walmart|';
    }

    // ---------------------------------------

    protected function getCustomLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Walmart Values');
    }

    protected function getCustomOptions()
    {
        $attributes = $this->getCustomOptionsAttributes();
        return !empty($attributes) ?
            $this->getOptions('Walmart\Magento\Product\Rule\Condition\Product', $attributes, ['walmart'])
            : [];
    }

    protected function getCustomOptionsAttributes()
    {
        $translation = $this->helperFactory->getObject('Module\Translation');

        return [
            'walmart_sku'                  => $translation->__('SKU'),
            'walmart_gtin'                 => $translation->__('GTIN'),
            'walmart_upc'                  => $translation->__('UPC'),
            'walmart_ean'                  => $translation->__('EAN'),
            'walmart_isbn'                 => $translation->__('ISBN'),
            'walmart_wpid'                 => $translation->__('Walmart ID'),
            'walmart_item_id'              => $translation->__('Item ID'),
            'walmart_online_qty'           => $translation->__('QTY'),
            'walmart_online_price'         => $translation->__('Price'),
            'walmart_start_date'           => $translation->__('Start Date'),
            'walmart_end_date'             => $translation->__('End Date'),
            'walmart_status'               => $translation->__('Status'),
            'walmart_details_data_changed' => $translation->__('Item Details need to be updated'),
            'walmart_online_price_invalid' => $translation->__('Pricing Rules violated'),
        ];
    }

    //########################################
}
