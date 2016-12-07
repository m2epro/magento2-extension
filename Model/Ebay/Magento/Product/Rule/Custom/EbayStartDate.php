<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

class EbayStartDate extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'ebay_start_date';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Start Date');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $startDate = $product->getData('start_date');
        if (empty($startDate)) {
            return null;
        }

        $startDate = $this->localeDate->formatDate($startDate, \IntlDateFormatter::MEDIUM, true);
        return strtotime($startDate);
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