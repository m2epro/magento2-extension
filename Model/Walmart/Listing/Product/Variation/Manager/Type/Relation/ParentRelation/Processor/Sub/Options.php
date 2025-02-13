<?php

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

class Options extends AbstractModel
{
    private \Ess\M2ePro\Model\Walmart\AttributeMapping\OptionReplacer $optionReplacer;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\AttributeMapping\OptionReplacer $optionReplacer,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->optionReplacer = $optionReplacer;
    }

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

            if (
                !$typeModel->isActualProductAttributes() ||
                !$typeModel->isActualMatchedAttributes() ||
                ($typeModel->isVariationProductMatched() &&
                    !$typeModel->isActualProductVariation())
            ) {
                $typeModel->resetProductVariation();
            }

            if (
                $typeModel->isVariationProductMatched() &&
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

        if (empty($productOptions)) {
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

            if ($childListingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                continue;
            }

            if (
                $this->isCorrectMatched($childTypeModel)
                && $childTypeModel->isVariationProductMatched()
            ) {
                continue;
            }

            if (
                $childListingProduct->isLocked()
                || $walmartChildListingProduct->getSku()
            ) {
                continue;
            }

            $this->getProcessor()->tryToRemoveChildListingProduct($childListingProduct);
        }
    }

    private function matchNewChildren()
    {
        $productOptions = $this->getProcessor()->getTypeModel()->getNotRemovedUnusedProductOptions();

        foreach ($productOptions as $productOption) {
            $channelOption = $this->convertProductOptionToChannelOption($productOption);
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

    private function convertProductOptionToChannelOption(array $productOption): array
    {
        $matchedAttributes = $this->getProcessor()->getTypeModel()->getMatchedAttributes();
        $optionIds = $this->getProcessor()->getTypeModel()->getOptionIds();
        $walmartListingProduct = $this->getProcessor()->getWalmartListingProduct();
        $dictionaryProductTypeId = $walmartListingProduct->isExistsProductType()
            ? $walmartListingProduct->getProductType()->getDictionaryId()
            : null;

        $channelOption = [];
        foreach ($productOption as $optionAttribute => $optionValue) {
            $productTypeAttribute = $matchedAttributes[$optionAttribute];
            $optionId = $optionIds[$optionValue] ?? null;

            if (
                $dictionaryProductTypeId !== null
                && $optionId !== null
            ) {
                $optionValue = $this->optionReplacer->replace(
                    $dictionaryProductTypeId,
                    $productTypeAttribute,
                    $optionId,
                    $optionValue
                );
            }

            $channelOption[$matchedAttributes[$optionAttribute]] = $optionValue;
        }

        return $channelOption;
    }

    private function isCorrectMatched(
        \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel
    ): bool {
        $currentChannelOption = $childTypeModel->getChannelOptions();
        $channelOption = $this->convertProductOptionToChannelOption($childTypeModel->getProductOptions());

        if (count($currentChannelOption) !== count($channelOption)) {
            return false;
        }

        return $this->hashArray($currentChannelOption) === $this->hashArray($channelOption);
    }

    private function hashArray(array $data): string
    {
        /** @var \Ess\M2ePro\Helper\Data $dataHelper */
        $dataHelper = $this->helperFactory->getObject('Data');
        ksort($data);

        return $dataHelper->md5String(json_encode($data));
    }
}
