<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category\Details;
use Ess\M2ePro\Helper\Component\Ebay\Category;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category\MagentoCategoriesMode;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Manager;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard;

class GetChooserBlockHtml extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;
    private Details $categoryDetailsProvider;
    private Category $componentEbayCategory;
    private MagentoCategoriesMode $categoryMagentoModeDataProvider;

    public function __construct(
        Details $categoryDetailsProvider,
        ManagerFactory $wizardManagerFactory,
        Category $componentEbayCategory,
        MagentoCategoriesMode $categoryMagentoModeDataProvider,
        Context $context,
        Factory $factory
    ) {
        parent::__construct(
            $factory,
            $context
        );

        $this->categoryDetailsProvider = $categoryDetailsProvider;
        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->componentEbayCategory = $componentEbayCategory;
        $this->categoryMagentoModeDataProvider = $categoryMagentoModeDataProvider;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);
        $listing = $manager->getListing();
        $categoriesData = $this->getCategoriesDataByMode($manager);
        $categoriesData = $this->getCategoriesDataForChooserBlock($categoriesData);

        $chooserBlock = $this->getLayout()
                             ->createBlock(
                                 \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser::class
                             );
        $chooserBlock->setAccountId($listing->getAccountId());
        $chooserBlock->setMarketplaceId($listing->getMarketplaceId());
        $chooserBlock->setCategoryMode($this->getRequest()->getParam('category_mode'));

        $chooserBlock->setCategoriesData($categoriesData);

        $this->setAjaxContent($chooserBlock->toHtml());

        return $this->getResult();
    }

    protected function getCategoriesDataForChooserBlock($categoriesData)
    {
        $neededProducts = [];
        foreach ($this->getRequestIds('products_id') as $id) {
            $temp = [];
            foreach ($this->componentEbayCategory->getCategoriesTypes() as $categoryType) {
                isset($categoriesData[$id][$categoryType]) && $temp[$categoryType]
                    = $categoriesData[$id][$categoryType];
            }

            $neededProducts[$id] = $temp;
        }

        $first = reset($neededProducts);
        $resultData = $first;

        foreach ($neededProducts as $lp => $templatesData) {
            if (empty($resultData)) {
                return [];
            }

            foreach ($templatesData as $categoryType => $categoryData) {
                if (!isset($resultData[$categoryType])) {
                    continue;
                }

                !isset($first[$categoryType]['specific']) && $first[$categoryType]['specific'] = [];
                !isset($categoryData['specific']) && $categoryData['specific'] = [];

                if (
                    $first[$categoryType]['template_id'] !== $categoryData['template_id'] ||
                    $first[$categoryType]['is_custom_template'] !== $categoryData['is_custom_template'] ||
                    $first[$categoryType]['specific'] !== $categoryData['specific']
                ) {
                    $resultData[$categoryType]['template_id'] = null;
                    $resultData[$categoryType]['is_custom_template'] = null;
                    $resultData[$categoryType]['specific'] = [];
                }

                if (
                    $first[$categoryType]['mode'] !== $categoryData['mode'] ||
                    $first[$categoryType]['value'] !== $categoryData['value'] ||
                    $first[$categoryType]['path'] !== $categoryData['path']
                ) {
                    unset($resultData[$categoryType]);
                }
            }
        }

        return !$resultData ? [] : $resultData;
    }

    private function getCategoriesDataByMode(Manager $manager)
    {
        $listing = $manager->getListing();
        $wizardProducts = $manager->getNotProcessedProducts();

        if ($manager->getWizardType() === Wizard::TYPE_GENERAL) {
            $currentStepData = $manager->getStepData(
                StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP
            );
            $categoriesCustomTemplatesData = $currentStepData['custom_templates'] ?? [];
            $previousStepData = $manager->getStepData(
                StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_MODE
            );
            $mode = $previousStepData['mode'] ?? null;

            if ($mode === 'category') {
                return $this->categoryMagentoModeDataProvider->getTemplatesDataPerMagentoCategory(
                    $listing,
                    $categoriesCustomTemplatesData
                );
            }
        }

        return  $this->categoryDetailsProvider->getCategoriesDetails(
            $wizardProducts,
            $listing->getAccountId(),
            $listing->getMarketplaceId()
        );
    }
}
