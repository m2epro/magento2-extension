<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Condition;

/**
 * Class \Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Condition\Combine
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
        $this->setType('Amazon\Magento\Product\Rule\Condition\Combine');
    }

    //########################################

    protected function getConditionCombine()
    {
        return $this->getType() . '|amazon|';
    }

    // ---------------------------------------

    protected function getCustomLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Amazon Values');
    }

    protected function getCustomOptions()
    {
        $attributes = $this->getCustomOptionsAttributes();
        return !empty($attributes) ?
               $this->getOptions('Amazon\Magento\Product\Rule\Condition\Product', $attributes, ['amazon'])
               : [];
    }

    protected function getCustomOptionsAttributes()
    {
        $helper = $this->helperFactory->getObject('Module\Translation');
        return [
            'amazon_sku' => $helper->__('SKU'),
            'amazon_general_id' => $helper->__('ASIN/ISBN Value'),
            'amazon_general_id_state' => $helper->__('ASIN/ISBN Status'),
            'amazon_online_qty' => $helper->__('QTY'),
            'amazon_online_price' => $helper->__('Price'),
            'amazon_online_sale_price' => $helper->__('Sale Price'),
            'amazon_is_afn_chanel' => $helper->__('Fulfillment'),
            'amazon_is_repricing' => $helper->__('On Repricing'),
            'amazon_status' => $helper->__('Status')
        ];
    }

    //########################################
}
