<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage;
use M2E\Kaufland\Model\Listing\Wizard\Exception\NotFoundException;
use Magento\Framework\App\ResponseInterface;

trait WizardTrait
{
    private function redirectToIndex($id): ResponseInterface
    {
        return $this->_redirect('*/ebay_listing_wizard/index', [
            'id' => $id,
        ]);
    }

    private function loadManagerToRuntime(
        ManagerFactory $managerFactory,
        RuntimeStorage $runtimeStorage
    ): void {
        $manager = $managerFactory->createById($this->getWizardIdFromRequest());
        $runtimeStorage->setManager($manager);
    }

    private function getWizardIdFromRequest(): int
    {
        $id = (int)$this->getRequest()->getParam('id');
        if (empty($id)) {
            throw new NotFoundException('Params not valid.');
        }

        return $id;
    }
}
