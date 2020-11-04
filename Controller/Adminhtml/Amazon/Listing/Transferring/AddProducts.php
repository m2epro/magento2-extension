<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Transferring;

class AddProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    //########################################

    /** @var \Ess\M2ePro\Model\Listing $listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Transferring $transferring */
    protected $transferring;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\Amazon\Listing\Transferring $transferring
    ) {
        parent::__construct($amazonFactory, $context);

        $this->transferring = $transferring;
    }

    //########################################

    public function execute()
    {
        $this->listing = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $this->transferring->setListing($this->listing);

        /** @var \Ess\M2ePro\Model\Listing $targetListing */
        $targetListing = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            $this->transferring->getTargetListingId()
        );

        $isDifferentMarketplaces = $targetListing->getMarketplaceId() != $this->listing->getMarketplaceId();

        $productsIds = $this->getRequest()->getParam('products');
        $productsIds = explode(',', $productsIds);
        $productsIds = array_unique($productsIds);
        $productsIds = array_filter($productsIds);

        $collection = $this->amazonFactory->getObject('Listing_Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => ($productsIds)]);

        $ids = [];
        foreach ($collection->getItems() as $sourceListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $sourceListingProduct */
            $listingProduct = $targetListing->getChildObject()->addProductFromAnotherAmazonSite(
                $sourceListingProduct,
                $this->listing
            );

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                $this->transferring->setErrorsCount($this->transferring->getErrorsCount() + 1);
                continue;
            }

            $ids[] = $listingProduct->getId();
        }

        if ($isDifferentMarketplaces) {
            $existingIds = $targetListing->getChildObject()->getAddedListingProductsIds();
            $existingIds = array_values(array_unique(array_merge($existingIds, $ids)));

            $targetListing->getChildObject()->setData(
                'product_add_ids',
                $this->getHelper('Data')->jsonEncode($existingIds)
            );
            $targetListing->save();
        }

        if ($this->getRequest()->getParam('is_last_part')) {
            if ($this->transferring->getErrorsCount()) {
                $this->getMessageManager()->addErrorMessage(
                    $this->getHelper('Module_Translation')->__(
                        '%errors_count% product(s) were not added to the selected Listing.
                        Please view Log for the details.',
                        $this->transferring->getErrorsCount()
                    )
                );
            }

            $this->transferring->clearSession();
        }

        return $this->getResponse()->setBody($this->getHelper('Data')->jsonEncode(['result' => 'success']));
    }
}
