<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit;

class SaveTitle extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;
    /** @var \Ess\M2ePro\Model\ListingFactory */
    protected $listingFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    protected $listingResource;
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\ListingFactory $listingFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource
    ) {
        $this->listingResource = $listingResource;
        $this->listingFactory = $listingFactory;
        parent::__construct($ebayFactory, $context);
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        $listingId = (int)$this->getRequest()->getParam('id');
        $listingTitle = $this->getRequest()->getParam('title');
        $existingTitles = explode(',', $post['titles']);

        if (empty($listingTitle) || !$listingId) {
            $this->messageManager->addErrorMessage(__('Title or Listing ID is missing.'));
            return $this->_redirect('*/*/general', ['id' => $listingId]);
        }

        if (in_array($listingTitle, $existingTitles)) {
            $this->messageManager->addErrorMessage(__('This title is already in use.'));
            return $this->_redirect('*/*/general', ['id' => $listingId, '_current' => true]);
        }
        try {
            $listing = $this->listingFactory->create();
            $this->listingResource->load($listing, $listingId);
            $listing->setTitle($listingTitle);
            $this->listingResource->save($listing);

            $this->messageManager->addSuccessMessage(__('Title updated successfully.'));
            return $this->_redirect('*/*/general', ['id' => $listingId, '_current' => true]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while saving the title.'));
        }

        return $this->_redirect('*/*/index');
    }
}
