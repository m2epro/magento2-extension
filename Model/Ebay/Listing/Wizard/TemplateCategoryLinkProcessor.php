<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product as WizardProductResource;

class TemplateCategoryLinkProcessor
{
    private ModelFactory $modelFactory;

    private ActiveRecordFactory $activeRecordFactory;

    private WizardProductResource $wizardProductResource;

    public function __construct(
        ModelFactory $modelFactory,
        ActiveRecordFactory $activeRecordFactory,
        WizardProductResource $wizardProductResource
    ) {
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->wizardProductResource = $wizardProductResource;
    }

    public function process(
        Manager $manager,
        array $categoryData,
        array $listingProductIds
    ): TemplateCategoryLinkProcessorResult {
        $listing = $manager->getListing();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $converter->setAccountId($listing->getAccountId());
        $converter->setMarketplaceId($listing->getMarketplaceId());

        foreach ($categoryData as $type => $templateData) {
            $converter->setCategoryDataFromChooser($templateData, $type);
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $categoryTpl */
        $categoryTpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_Category'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_MAIN)
        );

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $categorySecondaryTpl */
        $categorySecondaryTpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_Category'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_SECONDARY)
        );

        /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory $storeTpl */
        $storeTpl = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_MAIN)
        );

        /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory $storeSecondaryTpl */
        $storeSecondaryTpl = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder')->build(
            $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory'),
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_SECONDARY)
        );

        $this->assignCategoriesToProducts(
            $categoryTpl,
            $categorySecondaryTpl,
            $storeTpl,
            $storeSecondaryTpl,
            $manager,
            $listingProductIds
        );

        return new \Ess\M2ePro\Model\Ebay\Listing\Wizard\TemplateCategoryLinkProcessorResult(
            $categoryTpl->getId() ? (int)$categoryTpl->getId() : null,
            $categorySecondaryTpl->getId() ? (int)$categorySecondaryTpl->getId() : null,
            $storeTpl->getId() ? (int)$storeTpl->getId() : null,
            $storeSecondaryTpl->getId() ? (int)$storeSecondaryTpl->getId() : null
        );
    }

    private function assignCategoriesToProducts(
        \Ess\M2ePro\Model\Ebay\Template\Category $categoryTpl,
        \Ess\M2ePro\Model\Ebay\Template\Category $categorySecondaryTpl,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory $storeTpl,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory $storeSecondaryTpl,
        Manager $manager,
        array $listingProductIds = []
    ) {
        foreach ($manager->getNotProcessedProducts($listingProductIds) as $wizardProduct) {
            $wizardProduct->setTemplateCategoryId($categoryTpl->getId());
            $wizardProduct->setTemplateCategorySecondaryId($categorySecondaryTpl->getId());
            $wizardProduct->setStoreCategoryId($storeTpl->getId());
            $wizardProduct->setStoreCategorySecondaryId($storeSecondaryTpl->getId());

            $this->wizardProductResource->save($wizardProduct);
        }
    }
}
