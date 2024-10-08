<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;

class CompleteModeManually extends EbayListingController
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

        $manager->completeStep(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP);

        return $this->redirectToIndex($manager->getWizardId());
    }
}
