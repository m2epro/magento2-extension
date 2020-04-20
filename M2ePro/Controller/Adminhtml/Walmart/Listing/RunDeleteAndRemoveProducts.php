<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\RunDeleteAndRemoveProducts
 */
class RunDeleteAndRemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\ActionAbstract
{
    public function execute()
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return $this->setRawContent('You should select Products');
        }

        $productsCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $productsCollection->addFieldToFilter('id', explode(',', $listingsProductsIds));

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $productsCollection->getItems();
        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();

        /** @var \Ess\M2ePro\Model\Listing\Product[][] $parentListingsProducts */
        $parentListingsProducts = [];
        /** @var \Ess\M2ePro\Model\Listing\Product[][] $childListingsProducts */
        $childListingsProducts = [];

        foreach ($listingsProducts as $index => $listingProduct) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if (!$walmartListingProduct->getVariationManager()->isRelationParentType()) {

                /** @var \Ess\M2ePro\Model\Listing\Product\LockManager $lockManager */
                $lockManager = $this->modelFactory->getObject('Listing_Product_LockManager');
                $lockManager->setListingProduct($listingProduct);

                $lockManager->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
                $lockManager->setLogsActionId($logsActionId);
                $lockManager->setLogsAction(\Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT);

                if ($lockManager->checkLocking()) {
                    unset($listingsProducts[$index]);
                }

                continue;
            }

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation $typeModel */
            $typeModel = $walmartListingProduct->getVariationManager()->getTypeModel();

            $tempChildListingsProducts = $typeModel->getChildListingsProducts();

            $isParentLocked = false;

            foreach ($tempChildListingsProducts as $tempChildListingProduct) {

                /** @var \Ess\M2ePro\Model\Listing\Product\LockManager $lockManager */
                $lockManager = $this->modelFactory->getObject('Listing_Product_LockManager');
                $lockManager->setListingProduct($tempChildListingProduct);

                $lockManager->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
                $lockManager->setLogsActionId($logsActionId);
                $lockManager->setLogsAction(\Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT);

                if ($lockManager->checkLocking()) {
                    $isParentLocked = true;
                    break;
                }
            }

            unset($listingsProducts[$index]);

            if (!$isParentLocked) {
                // @codingStandardsIgnoreLine
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

            $removeHandler = $this->modelFactory->getObject('Walmart_Listing_Product_RemoveHandler');
            $removeHandler->setListingProduct($listingProduct);
            $removeHandler->process();
        }

        foreach ($parentListingsProducts as $parentListingProduct) {
            $removeHandler = $this->modelFactory->getObject('Walmart_Listing_Product_RemoveHandler');
            $removeHandler->setListingProduct($parentListingProduct);
            $removeHandler->process();
        }

        $this->setJsonContent(['result' => 'success']);
        return $this->getResult();
    }
}
