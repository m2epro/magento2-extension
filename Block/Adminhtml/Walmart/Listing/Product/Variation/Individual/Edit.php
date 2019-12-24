<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Individual;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Individual\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Individual
{
    public $currentVariation = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductVariationEdit');
        // ---------------------------------------

        $this->setTemplate('walmart/listing/product/variation/individual/edit.phtml');
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

        /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
        $variation = reset($variations);

        /** @var $optionInstance \Ess\M2ePro\Model\Listing\Product\Variation\Option */
        foreach ($variation->getOptions(true) as $optionInstance) {
            $option = $optionInstance->getOption();
            $attribute = $optionInstance->getAttribute();
            $this->currentVariation[$attribute] = $option;
        }

        return parent::_beforeToHtml();
    }

    //########################################
}
