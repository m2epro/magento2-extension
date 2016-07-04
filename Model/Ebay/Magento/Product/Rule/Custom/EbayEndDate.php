<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

class EbayEndDate extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractCustom
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

        $endDate = new \DateTime($endDate);

        return strtotime($endDate->format('Y-m-d'));
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