<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Helper\Component\Ebay\Category as EbayCategory;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ListingMagentoCategoriesDataProcessor;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category\MagentoCategoriesMode;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Repository;

class ResetModeCategory extends EbayListingController
{
    use WizardTrait;

    private Repository $repository;
    private ManagerFactory $wizardManagerFactory;
    private ListingMagentoCategoriesDataProcessor $magentoCategoriesDataProcessor;
    private MagentoCategoriesMode $categoryMagentoModeDataProvider;

    public function __construct(
        Repository $repository,
        ManagerFactory $wizardManagerFactory,
        ListingMagentoCategoriesDataProcessor $magentoCategoriesDataProcessor,
        MagentoCategoriesMode $categoryMagentoModeDataProvider,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);

        $this->repository = $repository;
        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->magentoCategoriesDataProcessor = $magentoCategoriesDataProcessor;
        $this->categoryMagentoModeDataProvider = $categoryMagentoModeDataProvider;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);
        $listing = $manager->getListing();
        $ebayListing = $listing->getChildObject();
        $magentoCategoryIds = $this->getRequestIds('products_id');
        $wizardProductsIds = $this->magentoCategoriesDataProcessor
            ->getWizardProductIdsByMagentoCategory($magentoCategoryIds, $manager);

        if (!empty($wizardProductsIds)) {
            $this->repository->resetCategories($wizardProductsIds);
        }

        $categoryData = $this->categoryMagentoModeDataProvider->getTemplatesDataPerMagentoCategory($listing, []);

        foreach ($magentoCategoryIds as $categoryId) {
            if (isset($categoryData[$categoryId][EbayCategory::TYPE_EBAY_MAIN])) {
                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_primary_category', 'mode_category', $categoryId],
                    []
                );
            }

            if (isset($categoryData[$categoryId][EbayCategory::TYPE_STORE_MAIN])) {
                $ebayListing->updateLastPrimaryCategory(
                    ['ebay_store_primary_category', 'mode_category', $categoryId],
                    []
                );
            }
        }

        return $this->getResult();
    }
}
