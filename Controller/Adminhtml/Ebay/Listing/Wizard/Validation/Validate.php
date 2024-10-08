<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Validation;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Validator\ValidatorComposite;

class Validate extends EbayListingController
{
    use WizardTrait;

    private ManagerFactory $wizardManagerFactory;

    private ValidatorComposite $specificValidator;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        ValidatorComposite $specificValidator,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);

        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->specificValidator = $specificValidator;
    }

    public function execute()
    {
        $wizardProductsIdsString = $this->getRequest()->getParam('listing_product_ids');

        if (empty($wizardProductsIdsString)) {
            return $this->getResult();
        }

        $wizardProductsIds = explode(',', $wizardProductsIdsString);

        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);
        $productToValidate = $manager->getNotProcessedProducts($wizardProductsIds);

        if (empty($productToValidate)) {
            return $this->getResult();
        }

        $this->specificValidator->validate($productToValidate);

        return $this->getResult();
    }
}
