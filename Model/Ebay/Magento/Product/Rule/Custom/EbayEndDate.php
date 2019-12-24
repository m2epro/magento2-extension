<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom\EbayEndDate
 */
class EbayEndDate extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'ebay_end_date';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('End Date');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $endDate = $product->getData('end_date');
        if (empty($endDate)) {
            return null;
        }

        $endDate = $this->localeDate->formatDate($endDate, \IntlDateFormatter::MEDIUM, true);
        return strtotime($endDate);
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'date';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'date';
    }

    //########################################
}
