<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Review;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\CompleteProcessor;
use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Helper\Data\Session;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;

class Complete extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;
    private Session $sessionHelper;
    private CompleteProcessor $completeProcessor;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        CompleteProcessor $completeProcessor,
        Session $sessionHelper,
        Factory $factory,
        Context $context
    ) {
        parent::__construct($factory, $context);

        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->sessionHelper = $sessionHelper;
        $this->completeProcessor = $completeProcessor;
    }

    public function execute()
    {
        $backUrl = $this->getRequest()->getParam('next_url');

        if (empty($backUrl) || !($backUrl = base64_decode($backUrl))) {
            return $this->redirectToIndex($this->getWizardIdFromRequest());
        }

        $id = $this->getWizardIdFromRequest();
        $wizardManager = $this->wizardManagerFactory->createById($id);

        $listingProducts = $this->completeProcessor->process($wizardManager);

        $wizardManager->completeStep(
            StepDeclarationCollectionFactory::STEP_REVIEW,
        );
        $wizardManager->setProductCountTotal(count($listingProducts));

        if ($this->getRequest()->getParam('do_list')) {
            // temporary
            $ids = array_map(static function ($product) {
                return $product->getId();
            }, $listingProducts);
            $this->sessionHelper->setValue('added_products_ids', $ids);
        }

        return $this->_redirect($backUrl);
    }
}
