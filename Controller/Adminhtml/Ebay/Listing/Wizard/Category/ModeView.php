<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\StepAbstract;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\SelectMode;
use Ess\M2ePro\Model\Listing;

class ModeView extends StepAbstract
{
    use WizardTrait;

    protected function getStepNick(): string
    {
        return StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_MODE;
    }

    protected function process(Listing $listing)
    {
        if ($this->isNeedUseModeFromListingSetting($listing)) {
            return $this->redirectToNextStep($listing);
        }

        $this->addContent(
            $this->getLayout()->createBlock(
                SelectMode::class,
            ),
        );

        $this->getResultPage()
             ->getConfig()
             ->getTitle()
             ->prepend(__('Set Your Categories'));

        $this->setPageHelpLink('set-ebay-categories');

        return $this->getResult();
    }

    private function isNeedUseModeFromListingSetting(Listing $listing): bool
    {
        return !empty($listing->getChildObject()->getAddProductMode());
    }

    private function redirectToNextStep(Listing $listing): \Magento\Framework\App\ResponseInterface
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing $ebayListing */
        $ebayListing = $listing->getChildObject();

        $manager = $this->getWizardManager();
        $manager->setStepData(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_MODE, [
            'mode' => $ebayListing->getAddProductMode(),
        ]);

        $manager->completeStep(StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_MODE, true);

        return $this->redirectToIndex($manager->getWizardId());
    }
}
