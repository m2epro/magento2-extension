<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode as CategoryTemplateBlock;
use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use Ess\M2ePro\Helper\Data\Session as SessionHelper;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Dictionary\CategoryFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category\Details;
use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

class ValidateModeManually extends EbayListingController
{
    use WizardTrait;

    private const SESSION_DATA_KEY = 'ebay_listing_product_category_settings';

    private ManagerFactory $wizardManagerFactory;

    private SessionHelper $sessionHelper;

    private EbayCategory\Ebay $componentEbayCategoryEbay;

    private EbayCategory $componentEbayCategory;

    private Details $categoryDetailsProvider;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        CategoryFactory $categoryFactory,
        SessionHelper $sessionHelper,
        EbayCategory\Ebay $componentEbayCategoryEbay,
        EbayCategory $componentEbayCategory,
        Details $categoryDetailsProvider,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);
        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->sessionHelper = $sessionHelper;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->componentEbayCategory = $componentEbayCategory;
        $this->categoryDetailsProvider = $categoryDetailsProvider;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);

        $wizardProducts = $manager->getNotProcessedProducts();
        $listing = $manager->getListing();
        $categoriesData = $this->categoryDetailsProvider->getCategoriesDetails(
            $wizardProducts,
            $listing->getAccountId(),
            $listing->getMarketplaceId()
        );

        $validateSpecifics = $this->getRequest()->getParam('validate_specifics');
        $validateCategory = $this->getRequest()->getParam('validate_category');

        $failedProductsIds = [];
        $succeedProducersIds = [];

        foreach ($wizardProducts as $product) {
            if (!isset($categoriesData[$product->getId()])) {
                $failedProductsIds[] = $product->getId();
            }
        }

        foreach ($categoriesData as $listingProductId => $categoryData) {
            if (
                !isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]) ||
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['mode'] === TemplateCategory::CATEGORY_MODE_NONE
            ) {
                $validateCategory ? $failedProductsIds[] = $listingProductId
                    : $succeedProducersIds[] = $listingProductId;
                continue;
            }

            if (!$validateSpecifics) {
                $succeedProducersIds[] = $listingProductId;
                continue;
            }

            if ($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template'] !== null) {
                $succeedProducersIds[] = $listingProductId;
                continue;
            }

            $hasRequiredSpecifics = $this->componentEbayCategoryEbay->hasRequiredSpecifics(
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['value'],
                $listing->getMarketplaceId()
            );

            if (!$hasRequiredSpecifics) {
                $succeedProducersIds[] = $listingProductId;
                continue;
            }

            $failedProductsIds[] = $listingProductId;
        }

        $this->setJsonContent([
            'validation' => empty($failedProductsIds),
            'total_count' => count($failedProductsIds) + count($succeedProducersIds),
            'failed_count' => count($failedProductsIds),
            'failed_products' => $failedProductsIds,
        ]);

        return $this->getResult();
    }
}
