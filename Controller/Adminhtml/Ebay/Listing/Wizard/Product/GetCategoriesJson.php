<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Product;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\Add\Tree;

class GetCategoriesJson extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;
    private RuntimeStorage $uiListingRuntimeStorage;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        RuntimeStorage $uiListingRuntimeStorage,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);

        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->uiListingRuntimeStorage = $uiListingRuntimeStorage;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);

        $this->uiListingRuntimeStorage->setListing($manager->getListing());

        $stepData = $manager->getStepData(StepDeclarationCollectionFactory::STEP_SELECT_PRODUCTS);
        $selectedProductsIds = $stepData['products_ids'] ?? [];

        $treeBlock = $this->getLayout()
                          ->createBlock(Tree::class);
        $treeBlock->setSelectedIds($selectedProductsIds);

        $this->setAjaxContent($treeBlock->getCategoryChildrenJson($this->getRequest()->getParam('category')), false);

        return $this->getResult();
    }
}
