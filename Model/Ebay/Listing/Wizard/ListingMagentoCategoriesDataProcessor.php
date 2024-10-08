<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Helper\Component\Ebay\Category as EbayCategory;
use Ess\M2ePro\Helper\Magento\Category as CategoryHelper;
use Ess\M2ePro\Model\Listing;

class ListingMagentoCategoriesDataProcessor
{
    private TemplateCategoryLinkProcessor $categoryLinkProcessor;

    private CategoryHelper $categoryHelper;

    public function __construct(
        TemplateCategoryLinkProcessor $categoryLinkProcessor,
        CategoryHelper $categoryHelper
    ) {
        $this->categoryLinkProcessor = $categoryLinkProcessor;
        $this->categoryHelper = $categoryHelper;
    }

    public function assignBySameTemplate(array $templateData, Manager $manager, array $categoryIds): void
    {
        $listing = $manager->getListing();
        $productIds = $this->getWizardProductIdsByMagentoCategory($categoryIds, $manager);

        $mainCategoriesIds = $this->categoryLinkProcessor->process($manager, $templateData, $productIds);

        $this->updateListingCategoriesData($categoryIds, $listing, $templateData, $mainCategoriesIds);
    }

    public function assignByCategoriesData(array $templatesData, Manager $manager): void
    {
        foreach ($templatesData as $categoryId => $templateData) {
            $productIds = $this->getWizardProductIdsByMagentoCategory([$categoryId], $manager);
            if (!empty($productIds)) {
                $this->categoryLinkProcessor->process($manager, $templateData, $productIds);
            }
        }
    }

    public function getWizardProductIdsByMagentoCategory(array $categoryIds, Manager $manager): array
    {
        $assignedProductIds = $this->categoryHelper->getProductsFromCategories($categoryIds);

        $wizardProductsIds = [];

        foreach ($manager->getNotProcessedProducts() as $product) {
            if (in_array($product->getMagentoProductId(), $assignedProductIds)) {
                $wizardProductsIds[] = $product->getId();
            }
        }

        return $wizardProductsIds;
    }

    private function updateListingCategoriesData(
        array $categoryIds,
        Listing $listing,
        array $templateData,
        array $templateIds
    ): void {
        $ebayListing = $listing->getChildObject();

        foreach ($categoryIds as $categoryId) {
            if (isset($templateData[EbayCategory::TYPE_EBAY_MAIN])) {
                unset($templateData[EbayCategory::TYPE_EBAY_MAIN]['specific']);
                $templateData[EbayCategory::TYPE_EBAY_MAIN]['template_id'] =
                    $templateIds[EbayCategory::TYPE_EBAY_MAIN] ?? '';

                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_primary_category', 'mode_category', $categoryId],
                    $templateData[eBayCategory::TYPE_EBAY_MAIN]
                );
            }

            if (isset($templateData[EbayCategory::TYPE_STORE_MAIN])) {
                $templateData[EbayCategory::TYPE_STORE_MAIN]['template_id'] =
                    $templateIds[EbayCategory::TYPE_STORE_MAIN] ?? '';
                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_store_primary_category', 'mode_category', $categoryId],
                    $templateData[EbayCategory::TYPE_STORE_MAIN]
                );
            } else {
                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_store_primary_category', 'mode_category', $categoryId],
                    []
                );
            }
        }
    }
}
