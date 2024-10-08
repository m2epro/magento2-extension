<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Exception\NotFoundException;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;

class Index extends EbayListingController
{
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
        $id = (int)$this->getRequest()->getParam('id');

        if (empty($id)) {
            $this->getMessageManager()->addError(__('Cannot access Wizard, Wizard ID is missing.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        try {
            $manager = $this->wizardManagerFactory->createById($id);
        } catch (NotFoundException $e) {
            $this->getMessageManager()->addError(__('Wizard not found.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        if ($manager->isCompleted()) {
            return $this->_redirect('*/ebay_listing/index');
        }

        $currentStep = $manager->getCurrentStep();

        return $this->_redirect($currentStep->getRoute(), ['id' => $id]);
    }
}
