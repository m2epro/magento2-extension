<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit;

class SaveCategory extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit
{
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;
    /**@var \Ess\M2ePro\Model\Ebay\ListingFactory */
    protected $ebayListingFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing */
    protected $ebayListingResource;
    /** @var \Ess\M2ePro\Model\ListingFactory */
    protected $listingFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    protected $listingResource;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\Ebay\ListingFactory $ebayListingFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing $ebayListingResource,
        \Ess\M2ePro\Model\ListingFactory $listingFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource
    ) {
        parent::__construct($ebayFactory, $context);

        $this->cachePermanent = $cachePermanent;
        $this->listingResource = $listingResource;
        $this->listingFactory = $listingFactory;
        $this->ebayListingResource = $ebayListingResource;
        $this->ebayListingFactory = $ebayListingFactory;
    }

    public function execute()
    {
        $listingId = (int)$this->getRequest()->getParam('id');
        $mode = $this->getRequest()->getParam('mode');
        $listing = $this->listingFactory->create();
        $this->listingResource->load($listing, $listingId);

        try {
            $ebayListing = $this->ebayListingFactory->create();
            $this->ebayListingResource->load($ebayListing, $listingId);
            $ebayListing->setAddProductMode($mode);
            $this->ebayListingResource->save($ebayListing);

            /**
             * @see \Ess\M2ePro\Model\Listing::save()
             * @see \Ess\M2ePro\Model\Ebay\Listing::save()
             */
            $this->cachePermanent->removeTagValues('listing');

            $this->messageManager->addSuccessMessage(__('Category updated successfully.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while saving Category'));
        }

        return $this->_redirect('*/*/categories', ['id' => $listingId, '_current' => true]);
    }
}
