<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\CategoryRecommendationsProcessor;
use Ess\M2ePro\Helper\Magento\Category as CategoryHelper;

class SetCategoryByEbayRecommendation extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;

    private CategoryRecommendationsProcessor $recommendationsProcessor;

    private CategoryHelper $categoryHelper;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        CategoryRecommendationsProcessor $recommendationsProcessor,
        CategoryHelper $categoryHelper,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);

        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->categoryHelper = $categoryHelper;
        $this->recommendationsProcessor = $recommendationsProcessor;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);

        $this->setJsonContent(
            $this->recommendationsProcessor->process($manager)
        );

        return $this->getResult();
    }
}
