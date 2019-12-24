<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Model\Lock\Item\Manager;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\RunDeleteAndRemoveProducts
 */
class RunDeleteAndRemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\ActionAbstract
{
    public function execute()
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            $this->setAjaxContent('You should select Products');
            return $this->getResult();
        }

        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
                                                  ->getResource()
                                                  ->getNextActionId();

        $listingsProductsIds = explode(',', $listingsProductsIds);

        $listingsProductsCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $listingsProductsCollection->addFieldToFilter('id', $listingsProductsIds);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $listingsProductsCollection->getItems();

        /** @var \Ess\M2ePro\Model\Listing\Product[][] $parentListingsProducts */
        $parentListingsProducts = [];
        /** @var \Ess\M2ePro\Model\Listing\Product[][] $childListingsProducts */
        $childListingsProducts = [];

        foreach ($listingsProducts as $index => $listingProduct) {
            $listingLog = $this->activeRecordFactory->getObject('Walmart_Listing_Log');
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if (!$walmartListingProduct->getVariationManager()->isRelationParentType()) {
                if ($this->isLocked($listingProduct)) {
                    $listingLog->addProductMessage(
                        $listingProduct->getListingId(),
                        $listingProduct->getProductId(),
                        $listingProduct->getId(),
                        \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                        $logsActionId,
                        \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT,
                        $this->getHelper('Module\Translation')
                             ->__('Another Action is being processed. Try again when the Action is completed.'),
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                    );

                    unset($listingsProducts[$index]);
                }

                continue;
            }

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Parent $typeModel */
            $typeModel = $walmartListingProduct->getVariationManager()->getTypeModel();

            $tempChildListingsProducts = $typeModel->getChildListingsProducts();

            $isParentLocked = false;

            foreach ($tempChildListingsProducts as $tempChildListingProduct) {
                if ($this->isLocked($tempChildListingProduct)) {
                    $listingLog->addProductMessage(
                        $listingProduct->getListingId(),
                        $listingProduct->getProductId(),
                        $listingProduct->getId(),
                        \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                        $logsActionId,
                        \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT,
                        $this->getHelper('Module\Translation')
                             ->__('Another Action is being processed. Try again when the Action is completed.'),
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                    );

                    $isParentLocked = true;
                    break;
                }
            }

            unset($listingsProducts[$index]);

            if (!$isParentLocked) {
                $childListingsProducts          = array_merge($childListingsProducts, $tempChildListingsProducts);
                $parentListingsProducts[$index] = $listingProduct;
            }
        }

        $listingsProducts = array_merge($listingsProducts, $childListingsProducts);

        if (empty($listingsProducts) && empty($parentListingsProducts)) {
            $this->setJsonContent(['result' => 'error', 'action_id' => $logsActionId]);
            return $this->getResult();
        }

        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

        foreach ($listingsProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if (!$listingProduct->isNotListed()) {
                $connector = $dispatcher->getVirtualConnector(
                    'product',
                    'retire',
                    'entity',
                    ['sku' => $walmartListingProduct->getSku()],
                    null,
                    $listingProduct->getAccount()
                );

                try {
                    $dispatcher->process($connector);
                } catch (\Exception $exception) {
                    $this->getHelper('Module\Exception')->process($exception);
                }
            }

            $this->removeHandler($listingProduct);
        }

        foreach ($parentListingsProducts as $parentListingProduct) {
            $this->removeHandler($parentListingProduct);
        }

        $this->setJsonContent(['result' => 'success']);
        return $this->getResult();
    }

    private function removeHandler(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $walmartListingProduct                 = $listingProduct->getChildObject();
        $variationManager                      = $walmartListingProduct->getVariationManager();
        $parentWalmartListingProductForProcess = null;

        if ($variationManager->isRelationChildType()) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $parentWalmartListingProduct */
            $parentWalmartListingProduct = $variationManager
                ->getTypeModel()
                ->getWalmartParentListingProduct();

            $parentWalmartListingProductForProcess = $parentWalmartListingProduct;

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $variationManager->getTypeModel();

            if ($childTypeModel->isVariationProductMatched()) {
                $parentWalmartListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                    $variationManager->getTypeModel()->getProductOptions()
                );
            }
        }

        if (!$listingProduct->isNotListed()) {
            $listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)->save();
        }

        $listingProduct->delete();
        $listingProduct->isDeleted(true);

        if ($parentWalmartListingProductForProcess === null) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Parent $parentTypeModel */
        $parentTypeModel = $parentWalmartListingProductForProcess->getVariationManager()->getTypeModel();
        $parentTypeModel->getProcessor()->process();
    }

    private function isLocked($listingProduct)
    {
        if ($listingProduct->isSetProcessingLock(null)) {
            return true;
        }

        /** @var Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager');
        $lockItemManager->setNick($listingProduct->getComponentMode()
                                        .'_listing_product_'
                                        .$listingProduct->getId());
        if (!$lockItemManager->isExist()) {
            return false;
        }

        if ($lockItemManager->isInactiveMoreThanSeconds(1800)) {
            $lockItemManager->remove();
            return false;
        }

        return true;
    }
}
