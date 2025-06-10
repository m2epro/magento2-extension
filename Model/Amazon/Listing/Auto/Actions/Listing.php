<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Actions;

class Listing extends \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
{
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository,
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
    ) {
        parent::__construct($listing, $activeRecordFactory, $exceptionHelper);
        $this->templateProductTypeRepository = $templateProductTypeRepository;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param int $deletingMode
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if (
                $amazonListingProduct->getVariationManager()->isRelationParentType()
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

                $this->activeRecordFactory
                    ->getObject('Listing_Product_Instruction')
                    ->getResource()
                    ->addForComponent(
                        [
                            'listing_product_id' => $listingProduct->getId(),
                            'type' => $instructionType,
                            'initiator' => self::INSTRUCTION_INITIATOR,
                            'priority' => $listingProduct->isStoppable() ? 60 : 0,
                        ],
                        $listingProduct->getComponentMode()
                    );
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

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $amazonListingProduct->searchAsin();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Auto\Category\Group $amazonCategoryGroup */
        $amazonCategoryGroup = $categoryGroup->getChildObject();

        $params = [
            'template_product_type_id' => $amazonCategoryGroup->getAddingProductTypeTemplateId(),
        ];

        $this->processAddedListingProduct($listingProduct, $params);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing $listing
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $amazonListingProduct->searchAsin();

        $this->logAddedToMagentoProduct($listingProduct);

        /** @var \Ess\M2ePro\Model\Amazon\Listing $amazonListing */
        $amazonListing = $listing->getChildObject();

        $params = [
            'template_product_type_id' => $amazonListing->getAutoGlobalAddingProductTypeTemplateId(),
        ];

        $this->processAddedListingProduct($listingProduct, $params);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Ess\M2ePro\Model\Listing $listing
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $amazonListingProduct->searchAsin();

        /** @var \Ess\M2ePro\Model\Amazon\Listing $amazonListing */
        $amazonListing = $listing->getChildObject();

        $params = [
            'template_product_type_id' => $amazonListing->getAutoWebsiteAddingProductTypeTemplateId(),
        ];

        $this->processAddedListingProduct($listingProduct, $params);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param array $params
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processAddedListingProduct(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $params
    ): void {
        if (empty($params['template_product_type_id'])) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
            $amazonListingProduct->setTemplateProductTypeId($params['template_product_type_id']);
            $amazonListingProduct->setIsGeneralIdOwner(
                \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
            );

            $listingProduct->save();

            return;
        }

        $processor = $amazonListingProduct->getVariationManager()->getTypeModel()->getProcessor();

        if (
            $listingProduct->getMagentoProduct()->isBundleType()
            || $listingProduct->getMagentoProduct()->isSimpleTypeWithCustomOptions()
            || $listingProduct->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()
        ) {
            $processor->process();

            return;
        }

        $productTypeTemplate = $this->templateProductTypeRepository->find(
            (int)$params['template_product_type_id']
        );
        if ($productTypeTemplate === null) {
            return;
        }

        $possibleThemes = $productTypeTemplate->getDictionary()->getVariationThemes();

        $productAttributes = $amazonListingProduct->getVariationManager()
                                                  ->getTypeModel()
                                                  ->getProductAttributes();

        foreach ($possibleThemes as $theme) {
            if (count($theme['attributes']) !== count($productAttributes)) {
                continue;
            }

            $amazonListingProduct->setTemplateProductTypeId($params['template_product_type_id']);
            $amazonListingProduct->setIsGeneralIdOwner(
                \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
            );

            break;
        }

        $listingProduct->save();

        $processor->process();
    }
}
