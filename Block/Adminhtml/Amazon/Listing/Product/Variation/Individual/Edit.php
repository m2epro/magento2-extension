<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Individual;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Individual
{
    public $currentVariation = array();

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductVariationEdit');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/product/variation/individual/edit.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $variationManager = $this->getListingProduct()->getChildObject()->getVariationManager();

        $isVariationMatched = $variationManager->getTypeModel()->isVariationProductMatched();

        if (!$isVariationMatched) {
            return $this;
        }

        $variations = $this->getListingProduct()->getVariations(true);
        if (count($variations) <= 0) {
            throw new \Ess\M2ePro\Model\Exception('There are no variations for a variation product.', [
                'listing_product_id' => $this->getListingProduct()->getId()
            ]);
        }

        /* @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
        $variation = reset($variations);

        /* @var $optionInstance \Ess\M2ePro\Model\Listing\Product\Variation\Option */
        foreach ($variation->getOptions(true) as $optionInstance) {
            $option = $optionInstance->getOption();
            $attribute = $optionInstance->getAttribute();
            $this->currentVariation[$attribute] = $option;
        }

        return parent::_beforeToHtml();
    }

    //########################################
}