<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\ProductSource;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode;
use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Select extends EbayListingController implements HttpPostActionInterface
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

        $source = $this->getRequest()->getPost('source');
        $allowedSources = [
            SourceMode::MODE_PRODUCT,
            SourceMode::MODE_CATEGORY,
        ];

        if (!in_array($source, $allowedSources)) {
            return $this->redirectToIndex($id);
        }

        $wizardManager = $this->wizardManagerFactory->createById($id);
        if (!$wizardManager->isCurrentStepIs(StepDeclarationCollectionFactory::STEP_SELECT_PRODUCT_SOURCE)) {
            return $this->redirectToIndex($id);
        }

        $wizardManager->setStepData(
            StepDeclarationCollectionFactory::STEP_SELECT_PRODUCT_SOURCE,
            [
                'source' => $source,
            ],
        );

        $wizardManager->completeStep(StepDeclarationCollectionFactory::STEP_SELECT_PRODUCT_SOURCE);

        return $this->redirectToIndex($id);
    }
}
