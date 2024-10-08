<?php

namespace Ess\M2ePro\Model\Walmart\Listing\Auto\Actions;

class Listing extends \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
{
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
    ) {
        parent::__construct($listing, $activeRecordFactory, $exceptionHelper);

        $this->productTypeRepository = $productTypeRepository;
    }

    public function deleteProduct(\Magento\Catalog\Model\Product $product, int $deletingMode): void
    {
        if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE) {
            return;
        }

        $listingsProducts = $this->getListing()->getProducts(true, ['product_id' => (int)$product->getId()]);

        if (count($listingsProducts) <= 0) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $parentsForRemove */
        $parentsForRemove = [];

        foreach ($listingsProducts as $listingProduct) {
            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                return;
            }

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if (
                $walmartListingProduct->getVariationManager()->isRelationParentType()
                && $deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE
            ) {
                $parentsForRemove[$listingProduct->getId()] = $listingProduct;
                continue;
            }

            try {
                $instructionType = self::INSTRUCTION_TYPE_STOP;

                if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE) {
                    $instructionType = self::INSTRUCTION_TYPE_STOP_AND_REMOVE;
                }

                /** @var \Ess\M2ePro\Model\Listing\Product\Instruction $instruction */
                $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
                $instruction->setData(
                    [
                        'listing_product_id' => $listingProduct->getId(),
                        'component' => $listingProduct->getComponentMode(),
                        'type' => $instructionType,
                        'initiator' => self::INSTRUCTION_INITIATOR,
                        'priority' => $listingProduct->isStoppable() ? 60 : 0,
                    ]
                );
                $instruction->save();
            } catch (\Exception $exception) {
                $this->exceptionHelper->process($exception);
            }
        }

        if (empty($parentsForRemove)) {
            return;
        }

        foreach ($parentsForRemove as $parentListingProduct) {
            $parentListingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
            $parentListingProduct->delete();
        }
    }

    public function addProductByCategoryGroup(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup
    ) {
        $logData = [
            'reason' => __METHOD__,
            'rule_id' => $categoryGroup->getId(),
            'rule_title' => $categoryGroup->getTitle(),
        ];
        $listingProduct = $this->getListing()->addProduct(
            $product,
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            false,
            true,
            $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Auto\Category\Group $walmartCategoryGroup */
        $walmartCategoryGroup = $categoryGroup->getChildObject();
        $this->processAddedListingProduct(
            $walmartCategoryGroup->getProductTypeId(),
            $listingProduct
        );
    }

    public function addProductByGlobalListing(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing $listing
    ) {
        $logData = [
            'reason' => __METHOD__,
        ];
        $listingProduct = $this->getListing()->addProduct(
            $product,
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            false,
            true,
            $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        $this->logAddedToMagentoProduct($listingProduct);

        /** @var \Ess\M2ePro\Model\Walmart\Listing $walmartListing */
        $walmartListing = $listing->getChildObject();
        $this->processAddedListingProduct(
            $walmartListing->getAutoGlobalAddingProductTypeId(),
            $listingProduct
        );
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing $listing
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductByWebsiteListing(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing $listing
    ) {
        $logData = [
            'reason' => __METHOD__,
        ];
        $listingProduct = $this->getListing()->addProduct(
            $product,
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            false,
            true,
            $logData
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing $walmartListing */
        $walmartListing = $listing->getChildObject();
        $this->processAddedListingProduct(
            $walmartListing->getAutoWebsiteProductTypeId(),
            $listingProduct
        );
    }

    private function processAddedListingProduct(
        int $productTypeId,
        \Ess\M2ePro\Model\Listing\Product $listingProduct
    ): void {
        if (!$this->productTypeRepository->isExists($productTypeId)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $walmartListingProduct->setData(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_PRODUCT_TYPE_ID,
            $productTypeId
        );
        $walmartListingProduct->save();

        if ($walmartListingProduct->getVariationManager()->isRelationParentType()) {
            $walmartListingProduct->addVariationAttributes();
        }
    }
}
