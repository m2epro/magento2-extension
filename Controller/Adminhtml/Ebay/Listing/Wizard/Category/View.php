<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\SelectMode;
use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\StepAbstract;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Helper\Data\Session as SessionHelper;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Dictionary\CategoryFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ListingMagentoCategoriesDataProcessor;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category\Details;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category\MagentoCategoriesMode;

class View extends StepAbstract
{
    use WizardTrait;

    private const SESSION_DATA_KEY = 'ebay_listing_product_category_settings';

    private CategoryFactory $categoryFactory;
    private SessionHelper $sessionHelper;
    private Details $categoryDetailsProvider;
    private ListingMagentoCategoriesDataProcessor $magentoCategoriesDataProcessor;
    private MagentoCategoriesMode $categoryMagentoModeDataProvider;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        RuntimeStorage $uiListingRuntimeStorage,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        CategoryFactory $categoryFactory,
        SessionHelper $sessionHelper,
        Details $categoryDetailsProvider,
        MagentoCategoriesMode $categoryMagentoModeDataProvider,
        ListingMagentoCategoriesDataProcessor $magentoCategoriesDataProcessor,
        Context $context,
        Factory $factory
    ) {
        parent::__construct(
            $wizardManagerFactory,
            $uiListingRuntimeStorage,
            $uiWizardRuntimeStorage,
            $factory,
            $context
        );

        $this->categoryFactory = $categoryFactory;
        $this->sessionHelper = $sessionHelper;
        $this->categoryDetailsProvider = $categoryDetailsProvider;
        $this->categoryMagentoModeDataProvider = $categoryMagentoModeDataProvider;
        $this->magentoCategoriesDataProcessor = $magentoCategoriesDataProcessor;
    }

    protected function getStepNick(): string
    {
        return StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP;
    }

    protected function process(Listing $listing)
    {
        $manager = $this->getWizardManager();
        $selectedMode = $manager->getStepData(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_MODE);

        $mode = $selectedMode['mode'];

        if ($mode === SelectMode::MODE_SAME) {
            return $this->stepSelectCategoryModeSame();
        }

        if ($mode === SelectMode::MODE_MANUALLY) {
            return $this->stepSelectCategoryModeManually();
        }

        if ($mode === SelectMode::MODE_CATEGORY) {
            return $this->stepSelectByMagentoCategory();
        }

        if ($mode === SelectMode::MODE_EBAY_SUGGESTED) {
            return $this->stepGetSuggestedCategories();
        }

        throw new \LogicException('Category mode unknown.');
    }

    private function stepSelectCategoryModeSame()
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing $eBayListing */
        $eBayListing = $this->getWizardManager()->getListing()->getChildObject();
        $this->addContent(
            $this->getLayout()->createBlock(
                Category\Same::class,
                '',
                [
                    'data' => [
                        'categories_data' => $eBayListing->getPreviousCategoryChoiceForSameMode(),
                    ],
                ]
            ),
        );

        $this->getResultPage()
             ->getConfig()
             ->getTitle()
             ->prepend(__('Set Category (All Products same Category)'));

        return $this->getResult();
    }

    private function stepSelectCategoryModeManually()
    {
        $manager = $this->getWizardManager();
        $wizardProducts = $manager->getNotProcessedProducts();
        $listing = $manager->getListing();
        $categoriesData = $this->categoryDetailsProvider->getCategoriesDetails(
            $wizardProducts,
            $listing->getAccountId(),
            $listing->getMarketplaceId()
        );

        $block = $this
            ->getLayout()
            ->createBlock(
                Category\Manually::class,
                '',
                [
                    'categoriesData' => $categoriesData,
                ]
            );

        if ($this->getRequest()->isXmlHttpRequest()) {
            $block->getChildBlock('grid')->setCategoriesData($categoriesData);
            $this->setAjaxContent($block->getChildBlock('grid')->toHtml());

            return $this->getResult();
        }

        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            __('Set Category (Manually for each Product)')
        );

        return $this->getResult();
    }

    private function stepSelectByMagentoCategory()
    {
        $manager = $this->getWizardManager();
        $listing = $manager->getListing();
        $currentStepData = $manager->getStepData(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP);
        $isInitialized = $currentStepData['is_initialized'] ?? false;
        $categoriesCustomTemplatesData = $currentStepData['custom_templates'] ?? [];
        $categoriesData = $this->categoryMagentoModeDataProvider->getTemplatesDataPerMagentoCategory(
            $listing,
            $categoriesCustomTemplatesData
        );

        $block = $this
            ->getLayout()
            ->createBlock(
                Category\MagentoCategory::class,
                '',
                [
                    'categoriesData' => $categoriesData,
                    'listing' => $listing,
                ]
            );

        if ($this->getRequest()->isXmlHttpRequest()) {
            $block->getChildBlock('grid')->setCategoriesData($categoriesData);
            $block->getChildBlock('grid')->setCategoriesListing($listing);
            $this->setAjaxContent($block->getChildBlock('grid')->toHtml());

            return $this->getResult();
        }

        $this->addContent($block);

        if (!$isInitialized) {
            $this->magentoCategoriesDataProcessor->assignByCategoriesData($categoriesData, $manager);
            $manager->setStepData(
                StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP,
                ['is_initialized' => true]
            );
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            __('Set Category (Based On Magento Categories)')
        );

        return $this->getResult();
    }

    private function stepGetSuggestedCategories()
    {
        $manager = $this->getWizardManager();
        $wizardProducts = $manager->getNotProcessedProducts();
        $listing = $manager->getListing();
        $categoriesData = $this->categoryDetailsProvider->getCategoriesDetails(
            $wizardProducts,
            $listing->getAccountId(),
            $listing->getMarketplaceId()
        );

        $block = $this
            ->getLayout()
            ->createBlock(
                Category\EbayRecommended::class,
                '',
                [
                    'categoriesData' => $categoriesData,
                    'listing' => $listing,
                ]
            );

        if ($this->getRequest()->isXmlHttpRequest()) {
            $block->getChildBlock('grid')->setCategoriesData($categoriesData);
            $block->getChildBlock('grid')->setCategoriesListing($listing);
            $this->setAjaxContent($block->getChildBlock('grid')->toHtml());

            return $this->getResult();
        }

        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            __('Get suggested Categories')
        );

        return $this->getResult();
    }
}
