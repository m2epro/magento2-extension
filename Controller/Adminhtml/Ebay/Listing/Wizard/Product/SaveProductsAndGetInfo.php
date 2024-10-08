<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Product;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;

class SaveProductsAndGetInfo extends EbayListingController
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

        $productsIds = $stepData['products_ids'] ?? [];

        $checked = $this->getRequest()->getParam('checked_ids');
        $initial = $this->getRequest()->getParam('initial_checked_ids');

        $checked = explode(',', $checked);
        $initial = explode(',', $initial);

        $initial = array_values(array_unique(array_merge($initial, $checked)));
        $productsIds = array_values(array_unique(array_merge($productsIds, $initial)));

        $productsIds = array_flip($productsIds);

        foreach (array_diff($initial, $checked) as $id) {
            unset($productsIds[$id]);
        }

        $stepData['products_ids'] = array_values(array_filter(array_flip($productsIds)));
        $manager->setStepData(StepDeclarationCollectionFactory::STEP_SELECT_PRODUCTS, $stepData);

        // ---------------------------------------

        $this->_forward('getTreeInfo');
    }
}
