<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Listing\Product\Save;

class After extends \Ess\M2ePro\Observer\AbstractModel
{
    //########################################

    public function process()
    {
        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->getEvent()->getData('object');

        $this->processIndexer($listingProduct);

        if ($listingProduct->isComponentModeEbay()) {
            $this->processEbayItemUUID($listingProduct);
        }
    }

    //########################################

    private function processIndexer(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $isChanged = false;
        foreach (\Ess\M2ePro\Model\Indexer\Listing\Product\VariationParent\Manager::getTrackedFields() as $fieldName) {
            if ($listingProduct->getData($fieldName) != $listingProduct->getOrigData($fieldName)) {
                $isChanged = true;
                break;
            }
        }

        if ($listingProduct->isObjectCreatingState()) {
            $isChanged = true;
        }

        if (!$isChanged) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Indexer\Listing\Product\VariationParent\Manager $manager */
        $manager = $this->modelFactory->getObject('Indexer\Listing\Product\VariationParent\Manager');
        $manager->setListing($listingProduct->getListing());
        $manager->markInvalidated();
    }

    private function processEbayItemUUID(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!$listingProduct->isComponentModeEbay()) {
            return;
        }

        $oldStatus = (int)$listingProduct->getOrigData('status');
        $newStatus = (int)$listingProduct->getData('status');

        $trackedStatuses = array(
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD,
        );

        if (!$listingProduct->isObjectCreatingState() &&
            ($oldStatus == $newStatus || !in_array($newStatus, $trackedStatuses))) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $childObject = $this->activeRecordFactory->getObject('Ebay\Listing\Product');
        $childObject->addData(array(
            'listing_product_id' => $ebayListingProduct->getId(),
            'item_uuid'          => $ebayListingProduct->generateItemUUID()
        ));

        $ebayListingProduct->getResource()->save($childObject);
    }

    //########################################
}