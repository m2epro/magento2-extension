<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Create as CreateModel;
use Ess\M2ePro\Model\ListingFactory;
use Ess\M2ePro\Model\ResourceModel\Listing as ListingResource;

class Create extends EbayListingController
{
    use WizardTrait;

    private ListingResource $listingResource;

    private ListingFactory $listingFactory;

    private CreateModel $createModel;

    public function __construct(
        ListingResource $listingResource,
        ListingFactory $listingFactory,
        CreateModel $createModel,
        Factory $ebayFactory,
        Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->listingResource = $listingResource;
        $this->listingFactory = $listingFactory;
        $this->createModel = $createModel;
    }

    public function execute()
    {
        $listingId = (int)$this->getRequest()->getParam('listing_id');
        $type = $this->getRequest()->getParam('type');

        if (empty($listingId) || empty($type)) {
            $this->getMessageManager()->addError(__('Cannot start Wizard, Listing must be created first.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        $listing = $this->listingFactory->create();

        //@todo Create Ebay Listing Repository
        $this->listingResource->load($listing, $listingId);

        $wizard = $this->createModel->process($listing, $type);

        return $this->redirectToIndex($wizard->getId());
    }
}
