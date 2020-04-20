<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\MatchedAttributes
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

        $channelAttributes = $this->getProcessor()->getTypeModel()->getChannelAttributes();

        if (count($channelAttributes) != count($matchedAttributes) ||
            array_diff($channelAttributes, array_values($matchedAttributes))
        ) {
            $this->getProcessor()->getTypeModel()->setMatchedAttributes([], false);
            return;
        }

        $possibleChannelAttributes = $this->getProcessor()->getPossibleChannelAttributes();

        if ($this->getProcessor()->getTypeModel()->getVirtualChannelAttributes()) {
            $matchedAttributes = $this->getProcessor()->getTypeModel()->getRealMatchedAttributes();
        }

        $channelMatchedAttributes = array_values($matchedAttributes);

        if (array_diff($channelMatchedAttributes, $possibleChannelAttributes)) {
            $this->getProcessor()->getTypeModel()->setMatchedAttributes([], false);
        }
    }

    protected function execute()
    {
        if ($this->getProcessor()->getTypeModel()->hasMatchedAttributes()) {
            return;
        }

        if (!$this->getProcessor()->getTypeModel()->hasChannelAttributes()) {
            return;
        }

        $channelAttributes = $this->getProcessor()->getTypeModel()->getChannelAttributes();

        $this->getProcessor()
            ->getTypeModel()
            ->setMatchedAttributes($this->matchAttributes($channelAttributes), false);
    }

    //########################################

    private function matchAttributes($channelAttributes)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute $attributeMatcher */
        $attributeMatcher = $this->modelFactory->getObject('Walmart_Listing_Product_Variation_Matcher_Attribute');
        $attributeMatcher->setMagentoProduct($this->getProcessor()->getListingProduct()->getMagentoProduct());
        $attributeMatcher->setDestinationAttributes($channelAttributes);
        $attributeMatcher->canUseDictionary(true);

        if (!$attributeMatcher->isAmountEqual() || !$attributeMatcher->isFullyMatched()) {
            return [];
        }

        return $attributeMatcher->getMatchedAttributes();
    }

    //########################################
}
