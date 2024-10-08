<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ListingMagentoCategoriesDataProcessor;
use Ess\M2ePro\Helper\Magento\Category as CategoryHelper;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;

class AssignModeCategory extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;

    private ListingMagentoCategoriesDataProcessor $magentoCategoriesDataProcessor;

    private CategoryHelper $categoryHelper;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        ListingMagentoCategoriesDataProcessor $magentoCategoriesDataProcessor,
        CategoryHelper $categoryHelper,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);

        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->magentoCategoriesDataProcessor = $magentoCategoriesDataProcessor;
        $this->categoryHelper = $categoryHelper;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);

        $templateData = $this->getRequest()->getParam('template_data');
        $templateData = (array)\Ess\M2ePro\Helper\Json::decode($templateData);
        /**
         * magento category ids (legacy implementation)
         */
        $magentoCategoryIds = $this->getRequestIds('products_id');

        $this->magentoCategoriesDataProcessor->assignBySameTemplate($templateData, $manager, $magentoCategoryIds);
        $currentStepData = $manager->getStepData(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP);
        foreach ($magentoCategoryIds as $id) {
            if (isset($templateData[0]['is_custom_template']) && $templateData[0]['is_custom_template'] == 1) {
                $currentStepData['custom_templates'][$id] = true;
            } else {
                unset($currentStepData['custom_templates'][$id]);
            }
        }

        $manager->setStepData(
            StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP,
            $currentStepData
        );

        return $this->getResult();
    }
}
