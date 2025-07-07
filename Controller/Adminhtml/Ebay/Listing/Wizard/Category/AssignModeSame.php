<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\TemplateCategoryLinkProcessor;

class AssignModeSame extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;
    private TemplateCategoryLinkProcessor $templateCategoryLinkProcessor;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        TemplateCategoryLinkProcessor $templateCategoryLinkProcessor,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);
        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->templateCategoryLinkProcessor = $templateCategoryLinkProcessor;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);

        $categoryData = [];
        $param = $this->getRequest()->getParam('category_data');

        if ($param) {
            $categoryData = json_decode($param, true);
        }

        if (empty($categoryData)) {
            $this->redirectToIndex($id);
        }

        $result = $this->templateCategoryLinkProcessor->process($manager, $categoryData, []);

        /** @var \Ess\M2ePro\Model\Ebay\Listing $eBayListing */
        $eBayListing = $manager->getListing()->getChildObject();
        $eBayListing->updateCategoryChoiceForSameMode($result);
        $eBayListing->save();

        $manager->completeStep(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP);

        return $this->redirectToIndex($id);
    }
}
