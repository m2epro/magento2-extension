<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Exception\NotFoundException;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Manager;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage as ListingRuntimeStorage;

abstract class StepAbstract extends EbayListingController
{
    use WizardTrait;

    protected ManagerFactory $wizardManagerFactory;
    protected ListingRuntimeStorage $uiListingRuntimeStorage;
    protected WizardRuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        ListingRuntimeStorage $uiListingRuntimeStorage,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        Factory $factory,
        Context $context
    ) {
        parent::__construct($factory, $context);

        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->uiListingRuntimeStorage = $uiListingRuntimeStorage;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
    }

    abstract protected function getStepNick(): string;

    abstract protected function process(Listing $listing);

    public function execute()
    {
        try {
            $this->initWizard();
        } catch (NotFoundException $e) {
            $this->getMessageManager()->addError(__('Wizard not found.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        if ($this->getWizardManager()->isCompleted()) {
            return $this->_redirect('*/ebay_listing/index');
        }

        if ($this->getWizardManager()->getCurrentStep()->getNick() !== $this->getStepNick()) {
            $this->getMessageManager()->addError(__('Please complete the current step to proceed.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        $this->uiListingRuntimeStorage->setListing($this->getWizardManager()->getListing());

        return $this->process($this->getWizardManager()->getListing());
    }

    private function initWizard(): void
    {
        $this->loadManagerToRuntime($this->wizardManagerFactory, $this->uiWizardRuntimeStorage);
    }

    protected function getWizardManager(): ?Manager
    {
        return $this->uiWizardRuntimeStorage->getManager();
    }
}
