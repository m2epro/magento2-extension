<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Product;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\Add\Tree;

class RemoveProductsByCategory extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);

        $this->wizardManagerFactory = $wizardManagerFactory;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);
        $stepData = $manager->getStepData(StepDeclarationCollectionFactory::STEP_SELECT_PRODUCTS);

        $selectedProductsIds = $stepData['products_ids'] ?? [];
        $categoriesIds = explode(',', $this->getRequest()->getParam('ids'));

        if (empty($selectedProductsIds)) {
            return;
        }

        $treeBlock = $this->getLayout()
                          ->createBlock(Tree::class);
        $treeBlock->setSelectedIds($selectedProductsIds);

        $productsForEachCategory = $treeBlock->getProductsForEachCategory();

        $products = [];

        foreach ($categoriesIds as $categoryId) {
            $products = array_merge($products, $productsForEachCategory[$categoryId]);
        }

        $stepData['products_ids'] = array_diff($selectedProductsIds, $products);
        $manager->setStepData(StepDeclarationCollectionFactory::STEP_SELECT_PRODUCTS, $stepData);
    }
}
