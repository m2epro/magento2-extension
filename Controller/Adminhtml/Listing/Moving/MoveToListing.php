<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

class MoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing\Moving
{
    //########################################

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');

        $selectedProducts = (array)$this->getHelper('Data')->jsonDecode(
            $this->getRequest()->getParam('selectedProducts')
        );
        $listingId = (int)$this->getRequest()->getParam('listingId');

        /** @var \Ess\M2ePro\Model\Listing $listingInstance */
        $listingInstance = $this->parentFactory->getCachedObjectLoaded(
            $componentMode,'Listing',$listingId
        );

        $logModel = $this->activeRecordFactory->getObject('Listing\Log');
        $logModel->setComponentMode($componentMode);

        $variationUpdaterModel = ucwords($listingInstance->getComponentMode())
            .'\Listing\Product\Variation\Updater';

        /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Updater $variationUpdaterObject */
        $variationUpdaterObject = $this->modelFactory->getObject($variationUpdaterModel);
        $variationUpdaterObject->beforeMassProcessEvent();

        $errors = 0;
        foreach ($selectedProducts as $listingProductId) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProductInstance */
            $listingProductInstance = $this->parentFactory
                ->getObjectLoaded($componentMode,'Listing\Product',$listingProductId);

            if ($listingProductInstance->isSetProcessingLock() ||
                $listingProductInstance->isSetProcessingLock('in_action')) {

                $logModel->addProductMessage(
                    $listingProductInstance->getListingId(),
                    $listingProductInstance->getProductId(),
                    $listingProductInstance->getId(),
                    \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                    NULL,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_MOVE_TO_LISTING,
                    // M2ePro_TRANSLATIONS
                    // Product was not Moved because it is in progress state now
                    'Product was not Moved because it is in progress state now',
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                );

                $errors++;
                continue;
            }

            if (!$this->productCanBeMoved($listingProductInstance->getProductId(), $listingInstance)) {

                $logModel->addProductMessage(
                    $listingProductInstance->getListingId(),
                    $listingProductInstance->getProductId(),
                    $listingProductInstance->getId(),
                    \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                    NULL,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_MOVE_TO_LISTING,
                    // M2ePro_TRANSLATIONS
                    // Product was not Moved
                    'Product was not Moved',
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                );

                $errors++;
                continue;
            }

            $logModel->addProductMessage(
                $listingId,
                $listingProductInstance->getProductId(),
                $listingProductInstance->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                NULL,
                \Ess\M2ePro\Model\Listing\Log::ACTION_MOVE_TO_LISTING,
                // M2ePro_TRANSLATIONS
                // Product was successfully Moved
                'Product was successfully Moved',
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );

            $logModel->addProductMessage(
                $listingProductInstance->getListingId(),
                $listingProductInstance->getProductId(),
                $listingProductInstance->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                NULL,
                \Ess\M2ePro\Model\Listing\Log::ACTION_MOVE_TO_LISTING,
                // M2ePro_TRANSLATIONS
                // Product was successfully Moved
                'Product was successfully Moved',
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );

            $isStoresDifferent = $listingProductInstance->getListing()->getStoreId() != $listingInstance->getStoreId();

            $listingProductInstance->setData('listing_id', $listingId)->save();
            $listingProductInstance->setListing($listingInstance);

            if ($isStoresDifferent) {
                $method = 'get'.ucfirst(strtolower($componentMode)).'Item';
                if (!$listingProductInstance->isNotListed()) {
                    $item = $listingProductInstance->getChildObject()->$method();
                    if ($item) {
                        $item->setData('store_id', $listingInstance->getStoreId())->save();
                    }
                }
            }

            if ($listingProductInstance->isComponentModeAmazon()) {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProductInstance->getChildObject();
                $variationManager = $amazonListingProduct->getVariationManager();

                if ($variationManager->isRelationParentType()) {
                    $this->moveChildrenToListing($listingProductId, $listingInstance);
                }
            }

            if ($isStoresDifferent) {
                $variationUpdaterObject->process($listingProductInstance);
            }
        }

        $variationUpdaterObject->afterMassProcessEvent();

        if ($errors == 0) {
            $this->setJsonContent(array('result'=>'success'));
        } else {
            $this->setJsonContent(array('result'=>'error', 'errors'=>$errors));
        }

        return $this->getResult();
    }

    private function moveChildrenToListing($parentListingProductId, $listing)
    {
        $connection = $this->resourceConnection->getConnection();

        // Get child products ids
        // ---------------------------------------
        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable(),
                array('listing_product_id', 'sku')
            )
            ->where('`variation_parent_id` = ?',$parentListingProductId);
        $products = $connection->fetchPairs($dbSelect);

        if (!empty($products)) {
            $connection->update(
                $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable(),
                array(
                    'listing_id' => $listing->getId()
                ),
                '`id` IN (' . implode(',', array_keys($products)) . ')'
            );
        }

        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Amazon\Item')->getResource()->getMainTable(),
                array('id')
            )
            ->where('`account_id` = ?', $listing->getAccountId())
            ->where('`marketplace_id` = ?',$listing->getMarketplaceId())
            ->where('`sku` IN (?)', implode(',', array_values($products)));
        $items = $connection->fetchCol($dbSelect);

        if (!empty($items)) {
            $connection->update(
                $this->activeRecordFactory->getObject('Amazon\Item')->getResource()->getMainTable(),
                array(
                    'store_id' => $listing->getStoreId()
                ),
                '`id` IN ('.implode(',', $items).')'
            );
        }
    }

    //########################################
}