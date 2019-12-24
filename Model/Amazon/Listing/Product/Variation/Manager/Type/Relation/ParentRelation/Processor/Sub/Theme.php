<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\Theme
 */
class Theme extends AbstractModel
{
    //########################################

    protected function check()
    {
        $currentTheme = $this->getProcessor()->getTypeModel()->getChannelTheme();
        if (empty($currentTheme)) {
            return;
        }

        if (!$this->getProcessor()->isGeneralIdOwner()) {
            $this->getProcessor()->getTypeModel()->resetChannelTheme(false);
            return;
        }

        if (!$this->getProcessor()->getAmazonListingProduct()->isExistDescriptionTemplate() ||
            !$this->getProcessor()->getAmazonDescriptionTemplate()->isNewAsinAccepted()
        ) {
            $this->getProcessor()->getTypeModel()->resetChannelTheme(false);
            return;
        }

        $possibleThemes = $this->getProcessor()->getPossibleThemes();
        if (empty($possibleThemes[$currentTheme])) {
            $this->getProcessor()->getTypeModel()->resetChannelTheme(false);
            return;
        }

        if (!$this->getProcessor()->getTypeModel()->isActualChannelTheme()) {
            $this->getProcessor()->getTypeModel()->resetChannelTheme(false);
        }
    }

    protected function execute()
    {
        if ($this->getProcessor()->getTypeModel()->getChannelTheme() || !$this->getProcessor()->isGeneralIdOwner()) {
            return;
        }

        $possibleThemes = $this->getProcessor()->getPossibleThemes();

        if (!$this->getProcessor()->getAmazonListingProduct()->isExistDescriptionTemplate() ||
            !$this->getProcessor()->getAmazonDescriptionTemplate()->isNewAsinAccepted() ||
            empty($possibleThemes)
        ) {
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
        $possibleThemes = $this->getProcessor()->getPossibleThemes();
        $channelAttributes = array_keys(
            $this->getProcessor()->getTypeModel()->getRealChannelAttributesSets()
        );

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Theme $themeMatcher */
        $themeMatcher = $this->modelFactory->getObject('Amazon_Listing_Product_Variation_Matcher_Theme');
        $themeMatcher->setThemes($possibleThemes);
        $themeMatcher->setSourceAttributes($channelAttributes);

        $matchedTheme = $themeMatcher->getMatchedTheme();
        if ($matchedTheme === null) {
            return;
        }

        $this->getProcessor()->getTypeModel()->setChannelTheme($matchedTheme, false, false);
    }

    private function processNewProduct()
    {
        $possibleThemes = $this->getProcessor()->getPossibleThemes();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Theme $themeMatcher */
        $themeMatcher = $this->modelFactory->getObject('Amazon_Listing_Product_Variation_Matcher_Theme');
        $themeMatcher->setThemes($possibleThemes);
        $themeMatcher->setMagentoProduct($this->getProcessor()->getListingProduct()->getMagentoProduct());

        $matchedTheme = $themeMatcher->getMatchedTheme();
        if ($matchedTheme === null) {
            return;
        }

        $this->getProcessor()->getTypeModel()->setChannelTheme($matchedTheme, false, false);
    }

    //########################################
}
