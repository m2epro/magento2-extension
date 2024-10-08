<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Repository;

class ResetModeManually extends EbayListingController
{
    use WizardTrait;

    private Repository $repository;

    public function __construct(
        Repository $repository,
        Context $context,
        Factory $factory
    ) {
        parent::__construct($factory, $context);

        $this->repository = $repository;
    }

    public function execute()
    {

        $productIds = $this->getRequestIds('products_ids');

        if (!empty($productIds)) {
            $this->repository->resetCategories($productIds);
        }

        return $this->getResult();
    }
}
