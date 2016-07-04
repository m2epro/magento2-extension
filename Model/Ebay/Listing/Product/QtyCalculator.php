<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing getComponentListing()
 * @method \Ess\M2ePro\Model\Ebay\Template\SellingFormat getComponentSellingFormatTemplate()
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product getComponentProduct()
 */
namespace Ess\M2ePro\Model\Ebay\Listing\Product;

class QtyCalculator extends \Ess\M2ePro\Model\Listing\Product\QtyCalculator
{
    //########################################

    public function getVariationValue(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        if ($variation->getChildObject()->isDelete()) {
            return 0;
        }

        return parent::getVariationValue($variation);
    }

    //########################################

    protected function getOptionBaseValue(\Ess\M2ePro\Model\Listing\Product\Variation\Option $option)
    {
        if (!$option->getMagentoProduct()->isStatusEnabled() ||
            !$option->getMagentoProduct()->isStockAvailability()) {
            return 0;
        }

        if ($this->getSource('mode') == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT) {

            if (!$this->getMagentoProduct()->isStatusEnabled() ||
                !$this->getMagentoProduct()->isStockAvailability()) {
                return 0;
            }
        }

        return parent::getOptionBaseValue($option);
    }

    //########################################
}