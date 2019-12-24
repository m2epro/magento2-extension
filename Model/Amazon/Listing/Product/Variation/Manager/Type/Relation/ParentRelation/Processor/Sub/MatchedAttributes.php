<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\MatchedAttributes
 */
class MatchedAttributes extends AbstractModel
{
    //########################################

    protected function check()
    {
        if (!$this->getProcessor()->getTypeModel()->hasMatchedAttributes()) {
            return;
        }

        $productAttributes = $this->getProcessor()->getTypeModel()->getProductAttributes();
        $matchedAttributes = $this->getProcessor()->getTypeModel()->getMatchedAttributes();

        if (count($productAttributes) != count($matchedAttributes) ||
            array_diff($productAttributes, array_keys($matchedAttributes))
        ) {
            $this->getProcessor()->getTypeModel()->setMatchedAttributes([], false);
            return;
        }

        if ($this->getProcessor()->isGeneralIdSet()) {
            $channelAttributes = $this->getProcessor()->getTypeModel()->getChannelAttributes();

            if (count($channelAttributes) != count($matchedAttributes) ||
                array_diff($channelAttributes, array_values($matchedAttributes))
            ) {
                $this->getProcessor()->getTypeModel()->setMatchedAttributes([], false);
            }

            return;
        }

        if (!$this->getProcessor()->isGeneralIdOwner()) {
            $this->getProcessor()->getTypeModel()->setMatchedAttributes([], false);
            return;
        }

        $channelTheme = $this->getProcessor()->getTypeModel()->getChannelTheme();
        if (!$channelTheme) {
            $this->getProcessor()->getTypeModel()->setMatchedAttributes([], false);
            return;
        }

        $possibleThemes = $this->getProcessor()->getPossibleThemes();
        $themeAttributes = $possibleThemes[$channelTheme]['attributes'];

        if ($this->getProcessor()->getTypeModel()->getVirtualChannelAttributes()) {
            $matchedAttributes = $this->getProcessor()->getTypeModel()->getRealMatchedAttributes();
        }

        $channelMatchedAttributes = array_values($matchedAttributes);

        sort($themeAttributes);
        sort($channelMatchedAttributes);

        if ($themeAttributes != $channelMatchedAttributes) {
            $this->getProcessor()->getTypeModel()->setMatchedAttributes([], false);
        }
    }

    protected function execute()
    {
        if ($this->getProcessor()->getTypeModel()->hasMatchedAttributes()) {
            return;
        }

        if (!$this->getProcessor()->isGeneralIdOwner() && !$this->getProcessor()->isGeneralIdSet()) {
            return;
        }

        if (!$this->getProcessor()->isGeneralIdSet() && !$this->getProcessor()->getTypeModel()->getChannelTheme()) {
            return;
        }

        if ($this->getProcessor()->isGeneralIdSet()) {
            $this->processExistProduct();
            return;
        }

        $this->processNewProduct();
    }

    //########################################

    private function processExistProduct()
    {
        $channelAttributes = $this->getProcessor()->getTypeModel()->getChannelAttributes();

        $this->getProcessor()
            ->getTypeModel()
            ->setMatchedAttributes($this->matchAttributes($channelAttributes), false);
    }

    private function processNewProduct()
    {
        $channelThemes = $this->getProcessor()->getPossibleThemes();
        $themeAttributes = $channelThemes[$this->getProcessor()->getTypeModel()->getChannelTheme()]['attributes'];

        $this->getProcessor()
            ->getTypeModel()
            ->setMatchedAttributes($this->matchAttributes($themeAttributes), true);
    }

    // ---------------------------------------

    private function matchAttributes($channelAttributes, $canUseDictionary = true)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute $attributeMatcher */
        $attributeMatcher = $this->modelFactory->getObject('Amazon_Listing_Product_Variation_Matcher_Attribute');
        $attributeMatcher->setMagentoProduct($this->getProcessor()->getListingProduct()->getMagentoProduct());
        $attributeMatcher->setDestinationAttributes($channelAttributes);
        $attributeMatcher->canUseDictionary($canUseDictionary);

        if (!$attributeMatcher->isAmountEqual() || !$attributeMatcher->isFullyMatched()) {
            return [];
        }

        return $attributeMatcher->getMatchedAttributes();
    }

    //########################################
}
