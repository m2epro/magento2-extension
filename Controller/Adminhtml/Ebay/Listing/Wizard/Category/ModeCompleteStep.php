<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\SelectMode;

class ModeCompleteStep extends EbayListingController
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

        $mode = $this->getRequest()->getParam('mode');

        if (empty($mode)) {
            return $this->redirectToIndex($id);
        }

        if (
            !in_array(
                $mode,
                [
                    SelectMode::MODE_SAME,
                    SelectMode::MODE_MANUALLY,
                    SelectMode::MODE_CATEGORY,
                    SelectMode::MODE_EBAY_SUGGESTED
                ]
            )
        ) {
            throw new \LogicException(sprintf('Category mode %s not valid.', $mode));
        }

        $manager->setStepData(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_MODE, [
            'mode' => $mode,
        ]);

        $manager->completeStep(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_MODE);

        return $this->redirectToIndex($id);
    }
}
