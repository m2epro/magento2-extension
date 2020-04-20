<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\Options
 */
class Options extends AbstractModel
{
    //########################################

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Option $optionMatcher */
    private $optionMatcher = null;

    //########################################

    protected function check()
    {
        if (empty($this->getProcessor()->getTypeModel()->getChildListingsProducts())) {
            return;
        }

        if (!$this->getProcessor()->isGeneralIdOwner() && !$this->getProcessor()->isGeneralIdSet()) {
            foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {
                if ($listingProduct->isNotListed()) {
                    $this->getProcessor()->tryToRemoveChildListingProduct($listingProduct);
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();
                $amazonListingProduct->getVariationManager()->getTypeModel()->unsetChannelVariation();
                $amazonListingProduct->getVariationManager()->setIndividualType();
            }

            return;
        }

        $channelVariations = $this->getProcessor()->getTypeModel()->getChannelVariations();

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            /** @var ChildRelation $typeModel */
            $typeModel = $amazonListingProduct->getVariationManager()->getTypeModel();

            if (!$typeModel->isActualProductAttributes() ||
                !$typeModel->isActualMatchedAttributes() ||
                ($typeModel->isVariationProductMatched() &&
                !$typeModel->isActualProductVariation())
            ) {
                $typeModel->resetProductVariation();
            }

            if ($typeModel->isVariationChannelMatched()) {
                $currentChannelOptions = $channelVariations[$amazonListingProduct->getGeneralId()];
                $childChannelOptions   = $typeModel->getChannelOptions();

                if ($currentChannelOptions != $childChannelOptions) {
                    $typeModel->setChannelVariation($currentChannelOptions);
                }
            }

            if (!$typeModel->isVariationProductMatched() && !$typeModel->isVariationChannelMatched()) {
                $this->getProcessor()->tryToRemoveChildListingProduct($listingProduct);
                continue;
            }

            if ($typeModel->isVariationProductMatched() && $typeModel->isVariationChannelMatched() &&
                count($typeModel->getProductOptions()) != count($typeModel->getChannelOptions())
            ) {
                $this->getProcessor()->tryToRemoveChildListingProduct($listingProduct);
            }
        }
    }

    protected function execute()
    {
        if (!$this->getProcessor()->isGeneralIdSet()) {
            return;
        }

        if (!$this->getProcessor()->getTypeModel()->hasMatchedAttributes()) {
            return;
        }

        $this->matchExistingChildren();
        $this->deleteBrokenChildren();
        $this->matchNewChildren();

        if ($this->canCreateNewProductChildren()) {
            $this->createNewProductChildren();
        }

        if ($this->getProcessor()->getTypeModel()->hasMatchedAttributes()) {
            $this->setMatchedAttributesToChildren();
        }
    }

    //########################################

    private function matchExistingChildren()
    {
        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            if (!$childListingProduct->getId()) {
                continue;
            }

            /** @var ChildRelation $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if ($childTypeModel->isVariationChannelMatched() && $childTypeModel->isVariationProductMatched()) {
                continue;
            }

            if ($childTypeModel->isVariationChannelMatched()) {
                $this->matchEmptyProductOptionsChild($childListingProduct);
                continue;
            }

            if ($childListingProduct->isLocked()) {
                continue;
            }

            $this->matchEmptyChannelOptionsChild($childListingProduct);
        }
    }

    private function deleteBrokenChildren()
    {
        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var ChildRelation $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if ($childTypeModel->isVariationChannelMatched() && $childTypeModel->isVariationProductMatched()) {
                continue;
            }

            if (!$childTypeModel->isVariationChannelMatched() && !$childTypeModel->isVariationProductMatched()) {
                $this->getProcessor()->tryToRemoveChildListingProduct($childListingProduct);
                continue;
            }

            if ($childListingProduct->isLocked()
                || $childListingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
            ) {
                continue;
            }

            if (!$childTypeModel->isVariationProductMatched()) {
                $this->getProcessor()->tryToRemoveChildListingProduct($childListingProduct);
                continue;
            }

            if ($this->getProcessor()->isGeneralIdOwner()) {
                continue;
            }

            $this->getProcessor()->tryToRemoveChildListingProduct($childListingProduct);
        }
    }

    private function matchNewChildren()
    {
        $channelOptions = $this->getProcessor()->getTypeModel()->getUnusedChannelOptions();
        $productOptions = $this->getProcessor()->getTypeModel()->getNotRemovedUnusedProductOptions();

        if (empty($channelOptions) || empty($productOptions)) {
            return;
        }

        $matcher = $this->getOptionMatcher();
        $matcher->setDestinationOptions($channelOptions);

        foreach ($productOptions as $productOption) {
            $generalId = $matcher->getMatchedOptionGeneralId($productOption);
            if ($generalId === null) {
                continue;
            }

            $this->getProcessor()->getTypeModel()->createChildListingProduct(
                $productOption,
                $channelOptions[$generalId],
                $generalId
            );
        }
    }

    private function canCreateNewProductChildren()
    {
        $channelOptions = $this->getProcessor()->getTypeModel()->getUnusedChannelOptions();
        $productOptions = $this->getProcessor()->getTypeModel()->getNotRemovedUnusedProductOptions();

        if (!$this->getProcessor()->isGeneralIdOwner() || !empty($channelOptions) || empty($productOptions)) {
            return false;
        }

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var ChildRelation $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationProductMatched()) {
                return false;
            }
        }

        return true;
    }

    private function createNewProductChildren()
    {
        $productOptions = $this->getProcessor()->getTypeModel()->getNotRemovedUnusedProductOptions();

        foreach ($productOptions as $productOption) {
            $this->getProcessor()->getTypeModel()->createChildListingProduct($productOption);
        }
    }

    private function setMatchedAttributesToChildren()
    {
        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonChildListingProduct */
            $amazonChildListingProduct = $childListingProduct->getChildObject();
            $childTypeModel = $amazonChildListingProduct->getVariationManager()->getTypeModel();

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

    private function matchEmptyProductOptionsChild(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation $typeModel */
        $typeModel = $amazonListingProduct->getVariationManager()->getTypeModel();

        $channelOptions = $typeModel->getChannelOptions();
        $productOptions = array_merge(
            $this->getProcessor()->getTypeModel()->getNotRemovedUnusedProductOptions(),
            $this->getProcessor()->getTypeModel()->getUsedProductOptions(true)
        );

        $matcher = $this->getOptionMatcher();
        $matcher->setDestinationOptions([$amazonListingProduct->getGeneralId() => $channelOptions]);

        foreach ($productOptions as $productOption) {
            $generalId = $matcher->getMatchedOptionGeneralId($productOption);

            if ($generalId === null) {
                continue;
            }

            $existChild = $this->findChildByProductOptions($productOption);
            if ($existChild !== null) {
                $this->getProcessor()->tryToRemoveChildListingProduct($existChild);
            }

            $productVariation = $this->getProcessor()->getProductVariation($productOption);
            if (empty($productVariation)) {
                continue;
            }

            $typeModel->setProductVariation($productVariation);
            $listingProduct->save();

            break;
        }
    }

    private function matchEmptyChannelOptionsChild(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation $typeModel */
        $typeModel = $listingProduct->getChildObject()->getVariationManager()->getTypeModel();

        $channelOptions = array_merge(
            $this->getProcessor()->getTypeModel()->getUnusedChannelOptions(),
            $this->getProcessor()->getTypeModel()->getUsedChannelOptions(true)
        );

        if (empty($channelOptions)) {
            return;
        }

        if (!$typeModel->isVariationProductMatched()) {
            return;
        }

        $matcher = $this->getOptionMatcher();
        $matcher->setDestinationOptions($channelOptions);

        $generalId = $matcher->getMatchedOptionGeneralId($typeModel->getProductOptions());
        if ($generalId === null) {
            return;
        }

        $existChild = $this->findChildByChannelOptions($channelOptions[$generalId]);
        if ($existChild !== null) {
            $this->getProcessor()->tryToRemoveChildListingProduct($existChild);
        }

        $listingProduct->getChildObject()->setData('general_id', $generalId);
        $typeModel->setChannelVariation($channelOptions[$generalId]);
    }

    //########################################

    private function findChildByProductOptions(array $productOptions)
    {
        return $this->findChildByOptions($productOptions, 'product');
    }

    private function findChildByChannelOptions(array $channelOptions)
    {
        return $this->findChildByOptions($channelOptions, 'channel');
    }

    private function findChildByOptions(array $options, $type)
    {
        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var ChildRelation $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if ($type == 'product' &&
                $childTypeModel->isVariationProductMatched() &&
                $options == $childTypeModel->getProductOptions()
            ) {
                return $childListingProduct;
            }

            if ($type == 'channel' &&
                $childTypeModel->isVariationChannelMatched() &&
                $options == $childTypeModel->getChannelOptions()
            ) {
                return $childListingProduct;
            }
        }

        return null;
    }

    //########################################

    private function getOptionMatcher()
    {
        if ($this->optionMatcher !== null) {
            return $this->optionMatcher;
        }

        $this->optionMatcher = $this->modelFactory->getObject('Amazon_Listing_Product_Variation_Matcher_Option');
        $this->optionMatcher->setMagentoProduct($this->getProcessor()->getListingProduct()->getMagentoProduct());
        $this->optionMatcher->setMatchedAttributes($this->getProcessor()->getTypeModel()->getMatchedAttributes());

        return $this->optionMatcher;
    }

    //########################################
}
