<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\TemplateCategoryLinkProcessor;

class AssignModeManually extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;

    private TemplateCategoryLinkProcessor $templateCategoryLinkProcessor;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        TemplateCategoryLinkProcessor $templateCategoryLinkProcessor,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);
        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->templateCategoryLinkProcessor = $templateCategoryLinkProcessor;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);

        $templateData = $this->getRequest()->getParam('template_data');
        $templateData = (array)\Ess\M2ePro\Helper\Json::decode($templateData);

        $productIds = $this->getRequestIds('products_id');

        $this->templateCategoryLinkProcessor->process($manager, $templateData, $productIds);

        return $this->getResult();
    }
}
