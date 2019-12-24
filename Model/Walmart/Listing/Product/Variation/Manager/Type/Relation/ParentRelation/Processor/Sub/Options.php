<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\Options
 */
class Options extends AbstractModel
{
    //########################################

    protected function check()
    {
        if (empty($this->getProcessor()->getTypeModel()->getChildListingsProducts())) {
            return;
        }

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $typeModel */
            $typeModel = $walmartListingProduct->getVariationManager()->getTypeModel();

            if (!$typeModel->isActualProductAttributes() ||
                !$typeModel->isActualMatchedAttributes() ||
                ($typeModel->isVariationProductMatched() &&
                    !$typeModel->isActualProductVariation())
            ) {
                $typeModel->resetProductVariation();
            }

            if ($typeModel->isVariationProductMatched() &&
                count($typeModel->getProductOptions()) != count($typeModel->getChannelOptions())
            ) {
                $this->getProcessor()->tryToRemoveChildListingProduct($listingProduct);
            }
        }
    }

    protected function execute()
    {
        if (!$this->getProcessor()->getTypeModel()->hasMatchedAttributes()) {
            return;
        }

        $this->deleteBrokenChildren();

        if ($this->canCreateNewProductChildren()) {
            $this->matchNewChildren();
        }

        if ($this->getProcessor()->getTypeModel()->hasMatchedAttributes()) {
            $this->setMatchedAttributesToChildren();
        }
    }

    //########################################

    private function canCreateNewProductChildren()
    {
        $productOptions = $this->getProcessor()->getTypeModel()->getNotRemovedUnusedProductOptions();

        if (count($productOptions) <= 0) {
            return false;
        }

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationProductMatched()) {
                return false;
            }
        }

        return true;
    }

    private function deleteBrokenChildren()
    {
        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartChildListingProduct */
            $walmartChildListingProduct = $childListingProduct->getChildObject();

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $walmartChildListingProduct->getVariationManager()->getTypeModel();

            if ($childTypeModel->isVariationProductMatched()) {
                continue;
            }

            if ($childListingProduct->isLocked() || $walmartChildListingProduct->getSku()
                || $childListingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
            ) {
                continue;
            }

            $this->getProcessor()->tryToRemoveChildListingProduct($childListingProduct);
        }
    }

    private function matchNewChildren()
    {
        $productOptions = $this->getProcessor()->getTypeModel()->getNotRemovedUnusedProductOptions();
        $matchedAttributes = $this->getProcessor()->getTypeModel()->getMatchedAttributes();

        foreach ($productOptions as $productOption) {
            $channelOption = [];
            foreach ($productOption as $attribute => $value) {
                $channelOption[$matchedAttributes[$attribute]] = $value;
            }

            $this->getProcessor()->getTypeModel()->createChildListingProduct($productOption, $channelOption);
        }
    }

    private function setMatchedAttributesToChildren()
    {
        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartChildListingProduct */
            $walmartChildListingProduct = $childListingProduct->getChildObject();
            $childTypeModel = $walmartChildListingProduct->getVariationManager()->getTypeModel();

            $correctMatchedAttributes = $childTypeModel->getCorrectMatchedAttributes();

            if ($childTypeModel->isActualMatchedAttributes() && !empty($correctMatchedAttributes)) {
                continue;
            }

            $childTypeModel->setCorrectMatchedAttributes(
                $this->getProcessor()->getTypeModel()->getMatchedAttributes()
            );
        }
    }

    //########################################
}
