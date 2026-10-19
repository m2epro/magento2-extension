<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

class GetCategoryChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    private \Ess\M2ePro\Model\Ebay\Listing\Repository $ebayListRepository;
    private \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\ConverterFactory $converterFactory;
    private \Ess\M2ePro\Model\Ebay\Listing\Auto\Advanced\Filter\Repository $advancedFilterRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Repository $ebayListRepository,
        \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\ConverterFactory $converterFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Auto\Advanced\Filter\Repository $advancedFilterRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->ebayListRepository = $ebayListRepository;
        $this->converterFactory = $converterFactory;
        $this->advancedFilterRepository = $advancedFilterRepository;
    }

    public function execute()
    {
        $listingId = (int)$this->getRequest()->getParam('listing_id');
        $magentoCategoryId = (int)$this->getRequest()->getParam('magento_category_id');
        $autoMode = (int)$this->getRequest()->getParam('mode');
        $categoryGroupId = (int)$this->getRequest()->getParam('group_id');
        $advancedFilterId = (int)$this->getRequest()->getParam('advanced_filter_id');

        $listing = $this->ebayListRepository->get($listingId);

        $converter = $this->converterFactory
            ->create()
            ->setAccountId($listing->getAccountId())
            ->setMarketplaceId($listing->getMarketplaceId());

        $categoryTemplate = $this->getCategoryTemplate(
            $listing,
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN,
            $autoMode,
            $categoryGroupId,
            $magentoCategoryId,
            $advancedFilterId
        );

        if ($categoryTemplate !== null) {
            $converter->setCategoryDataFromTemplate(
                $categoryTemplate->getData(),
                \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN
            );
        }

        $categorySecondaryTemplate = $this->getCategoryTemplate(
            $listing,
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY,
            $autoMode,
            $categoryGroupId,
            $magentoCategoryId,
            $advancedFilterId
        );

        if ($categorySecondaryTemplate !== null) {
            $converter->setCategoryDataFromTemplate(
                $categorySecondaryTemplate->getData(),
                \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY
            );
        }

        $storeTemplate = $this->getStoreCategoryTemplate(
            $listing,
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN,
            $autoMode,
            $categoryGroupId,
            $magentoCategoryId,
            $advancedFilterId
        );
        if ($storeTemplate !== null) {
            $converter->setCategoryDataFromTemplate(
                $storeTemplate->getData(),
                \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN
            );
        }

        $storeSecondaryTemplate = $this->getStoreCategoryTemplate(
            $listing,
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY,
            $autoMode,
            $categoryGroupId,
            $magentoCategoryId,
            $advancedFilterId
        );
        if ($storeSecondaryTemplate !== null) {
            $converter->setCategoryDataFromTemplate(
                $storeSecondaryTemplate->getData(),
                \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY
            );
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser $chooserBlock */
        $chooserBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser::class);
        $chooserBlock->setAccountId($listing->getAccountId());
        $chooserBlock->setMarketplaceId($listing->getMarketplaceId());
        $chooserBlock->setCategoriesData($converter->getCategoryDataForChooser());

        $this->setAjaxContent($chooserBlock);

        return $this->getResult();
    }

    // ----------------------------------------

    protected function getCategoryTemplate(
        \Ess\M2ePro\Model\Listing $listing,
        int $categoryType,
        int $autoMode,
        int $groupId,
        int $magentoCategoryId,
        int $advancedFilterId
    ): ?\Ess\M2ePro\Model\Ebay\Template\Category {
        switch ($autoMode) {
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL:
                return $this->getGlobalModeCategoryTemplate($categoryType, $listing);
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE:
                return $this->getWebsiteModeCategoryTemplate($categoryType, $listing);
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY:
                return $this->getCategoryModeCategoryTemplate($categoryType, $magentoCategoryId, $groupId);
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_ADVANCED_FILTER:
                return $this->getAdvancedFilterModeCategoryTemplate($categoryType, $advancedFilterId);
        }

        return null;
    }

    private function getGlobalModeCategoryTemplate(
        int $categoryType,
        \Ess\M2ePro\Model\Listing $listing
    ): ?\Ess\M2ePro\Model\Ebay\Template\Category {
        /** @var \Ess\M2ePro\Model\Ebay\Listing $ebayListing */
        $ebayListing = $listing->getChildObject();
        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            return $ebayListing->getAutoGlobalAddingCategoryTemplate();
        }

        return $ebayListing->getAutoGlobalAddingCategorySecondaryTemplate();
    }

    private function getWebsiteModeCategoryTemplate(
        int $categoryType,
        \Ess\M2ePro\Model\Listing $listing
    ): ?\Ess\M2ePro\Model\Ebay\Template\Category {
        /** @var \Ess\M2ePro\Model\Ebay\Listing $ebayListing */
        $ebayListing = $listing->getChildObject();
        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            return $ebayListing->getAutoWebsiteAddingCategoryTemplate();
        }

        return $ebayListing->getAutoWebsiteAddingCategorySecondaryTemplate();
    }

    private function getCategoryModeCategoryTemplate(
        int $categoryType,
        int $magentoCategoryId,
        int $groupId
    ): ?\Ess\M2ePro\Model\Ebay\Template\Category {
        if (empty($magentoCategoryId)) {
            return null;
        }
        /** @var \Ess\M2ePro\Model\Listing\Auto\Category $autoCategory */
        $autoCategory = $this->activeRecordFactory
            ->getObject('Listing_Auto_Category')->getCollection()
            ->addFieldToFilter('group_id', $groupId)
            ->addFieldToFilter('category_id', $magentoCategoryId)
            ->getFirstItem();

        if ($autoCategory->isObjectNew()) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Auto\Category\Group $template */
        $template = $this->activeRecordFactory
            ->getObjectLoaded('Ebay_Listing_Auto_Category_Group', $groupId);

        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            return $template->getCategoryTemplate();
        }

        return $template->getCategorySecondaryTemplate();
    }

    private function getAdvancedFilterModeCategoryTemplate(
        int $categoryType,
        int $advancedFilterId
    ): ?\Ess\M2ePro\Model\Ebay\Template\Category {
        $advancedFilter = $this->advancedFilterRepository->find($advancedFilterId);
        if ($advancedFilter === null) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Auto\Advanced\Filter $ebayAdvancedFilter */
        $ebayAdvancedFilter = $advancedFilter->getChildObject();

        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            return $ebayAdvancedFilter->getAddingTemplateCategory();
        }

        return $ebayAdvancedFilter->getAddingTemplateCategorySecondary();
    }

    protected function getStoreCategoryTemplate(
        \Ess\M2ePro\Model\Listing $listing,
        int $categoryType,
        int $autoMode,
        int $groupId,
        int $magentoCategoryId,
        int $advancedFilterId
    ): ?\Ess\M2ePro\Model\Ebay\Template\StoreCategory {
        switch ($autoMode) {
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL:
                return $this->getGlobalModeStoreCategoryTemplate($categoryType, $listing);
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE:
                return $this->getWebsiteModeStoreCategoryTemplate($categoryType, $listing);
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY:
                return $this->getCategoryModeStoreCategoryTemplate($categoryType, $magentoCategoryId, $groupId);
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_ADVANCED_FILTER:
                return $this->getAdvancedFilterModeStoreCategoryTemplate($categoryType, $advancedFilterId);
        }

        return null;
    }

    private function getGlobalModeStoreCategoryTemplate(
        int $categoryType,
        \Ess\M2ePro\Model\Listing $listing
    ): ?\Ess\M2ePro\Model\Ebay\Template\StoreCategory {
        /** @var \Ess\M2ePro\Model\Ebay\Listing $ebayListing */
        $ebayListing = $listing->getChildObject();
        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            return $ebayListing->getAutoGlobalAddingStoreCategoryTemplate();
        }

        return $ebayListing->getAutoGlobalAddingStoreCategorySecondaryTemplate();
    }

    private function getWebsiteModeStoreCategoryTemplate(
        int $categoryType,
        \Ess\M2ePro\Model\Listing $listing
    ): ?\Ess\M2ePro\Model\Ebay\Template\StoreCategory {
        /** @var \Ess\M2ePro\Model\Ebay\Listing $ebayListing */
        $ebayListing = $listing->getChildObject();
        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            return $ebayListing->getAutoWebsiteAddingStoreCategoryTemplate();
        }

        return $ebayListing->getAutoWebsiteAddingStoreCategorySecondaryTemplate();
    }

    private function getCategoryModeStoreCategoryTemplate(
        int $categoryType,
        int $magentoCategoryId,
        int $groupId
    ): ?\Ess\M2ePro\Model\Ebay\Template\StoreCategory {
        if (empty($magentoCategoryId)) {
            return null;
        }
        /** @var \Ess\M2ePro\Model\Listing\Auto\Category $autoCategory */
        $autoCategory = $this->activeRecordFactory
            ->getObject('Listing_Auto_Category')->getCollection()
            ->addFieldToFilter('group_id', $groupId)
            ->addFieldToFilter('category_id', $magentoCategoryId)
            ->getFirstItem();

        if ($autoCategory->isObjectNew()) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Auto\Category\Group $template */
        $template = $this->activeRecordFactory
            ->getObjectLoaded('Ebay_Listing_Auto_Category_Group', $groupId);

        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN) {
            return $template->getStoreCategoryTemplate();
        }

        return $template->getStoreCategorySecondaryTemplate();
    }

    private function getAdvancedFilterModeStoreCategoryTemplate(
        int $categoryType,
        int $advancedFilterId
    ): ?\Ess\M2ePro\Model\Ebay\Template\StoreCategory {
        $advancedFilter = $this->advancedFilterRepository->find($advancedFilterId);
        if ($advancedFilter === null) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Auto\Advanced\Filter $ebayAdvancedFilter */
        $ebayAdvancedFilter = $advancedFilter->getChildObject();

        if ($categoryType == \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN) {
            return $ebayAdvancedFilter->getAddingTemplateStoreCategory();
        }

        return $ebayAdvancedFilter->getAddingTemplateStoreCategorySecondary();
    }
}
