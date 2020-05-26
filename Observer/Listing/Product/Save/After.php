<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Listing\Product\Save;

/**
 * Class \Ess\M2ePro\Observer\Listing\Product\Save\After
 */
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

    protected function processIndexer(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $resourceModel = $this->activeRecordFactory->getObject(
            ucfirst($listingProduct->getComponentMode()) . '\Listing\Product\Indexer\VariationParent'
        )->getResource();

        $isChanged = false;
        foreach ($resourceModel->getTrackedFields() as $fieldName) {
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

        /** @var \Ess\M2ePro\Model\Listing\Product\Indexer\VariationParent\Manager $manager */
        $manager = $this->modelFactory->getObject('Listing_Product_Indexer_VariationParent_Manager', [
            'listing' => $listingProduct->getListing()
        ]);
        $manager->markInvalidated();
    }

    protected function processEbayItemUUID(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!$listingProduct->isComponentModeEbay()) {
            return;
        }

        $oldStatus = (int)$listingProduct->getOrigData('status');
        $newStatus = (int)$listingProduct->getData('status');

        $trackedStatuses = [
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD,
        ];

        if (!$listingProduct->isObjectCreatingState() &&
            ($oldStatus == $newStatus || !in_array($newStatus, $trackedStatuses))) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        $ebayListingProduct->addData(
            [
                'item_uuid' => $ebayListingProduct->generateItemUUID()
            ]
        )->save();
    }

    //########################################
}
