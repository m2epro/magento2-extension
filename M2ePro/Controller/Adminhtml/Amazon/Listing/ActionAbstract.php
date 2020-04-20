<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\ActionAbstract
 */
abstract class ActionAbstract extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    //########################################

    protected function scheduleAction($action, array $params = [])
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return $this->setRawContent('You should select Products');
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $productsCollection */
        $productsCollection = $this->amazonFactory->getObject('Listing_Product')->getCollection();
        $productsCollection->addFieldToFilter('id', explode(',', $listingsProductsIds));

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $productsCollection->getItems();

        $childListingsProducts = [];

        foreach ($listingsProducts as $index => $listingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            $tempChildListingsProducts = $amazonListingProduct->getVariationManager()
                ->getTypeModel()
                ->getChildListingsProducts();

            if (empty($tempChildListingsProducts)) {
                continue;
            }

            if ($action != \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE) {
                unset($listingsProducts[$index]);
            }

            // @codingStandardsIgnoreLine
            $childListingsProducts = array_merge($childListingsProducts, $tempChildListingsProducts);
        }

        $listingsProducts = array_merge($listingsProducts, $childListingsProducts);
        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();

        $this->checkLocking($listingsProducts, $logsActionId, $action);
        if (empty($listingsProducts)) {
            $this->setJsonContent(['result' => 'error', 'action_id' => $logsActionId]);
            return $this->getResult();
        }

        $this->createUpdateScheduledActions(
            $listingsProducts,
            $action,
            $params
        );

        if (isset($params['switch_to'])) {
            $this->setJsonContent([
                'messages' => [[
                    'type' => 'success',
                    'text' => $this->__('Fulfillment switching is in progress now. Please wait.')
                ]]
            ]);
            return $this->getResult();
        }

        $this->setJsonContent(['result' => 'success', 'action_id' => $logsActionId]);
        return $this->getResult();
    }

    //########################################

    protected function checkLocking(&$listingsProducts, $logsActionId, $action)
    {
        foreach ($listingsProducts as $index => $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product\LockManager $lockManager */
            $lockManager = $this->modelFactory->getObject('Listing_Product_LockManager');
            $lockManager->setListingProduct($listingProduct);
            $lockManager->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
            $lockManager->setLogsActionId($logsActionId);
            $lockManager->setLogsAction($this->getLogsAction($action));

            if ($lockManager->checkLocking()) {
                unset($listingsProducts[$index]);
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     * @param int $action
     * @param array $params
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function createUpdateScheduledActions(&$listingsProducts, $action, array $params)
    {
        $listingsProductsIds = [];
        foreach ($listingsProducts as $listingProduct) {
            $listingsProductsIds[] = $listingProduct->getId();
        }

        $existedScheduled = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $existedScheduled->addFieldToFilter('listing_product_id', $listingsProductsIds);

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager $scheduledActionManager */
        $scheduledActionManager = $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');

        foreach ($listingsProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
            $scheduledAction = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction');
            $scheduledAction->setData(
                $this->createUpdateScheduledActionsDataCallback($listingProduct, $action, $params)
            );

            if ($existedScheduled->getItemByColumnValue('listing_product_id', $listingProduct->getId())) {
                $scheduledActionManager->updateAction($scheduledAction);
            } else {
                $scheduledActionManager->addAction($scheduledAction);
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param int $action
     * @param array $params
     */
    protected function createUpdateScheduledActionsDataCallback($listingProduct, $action, array $params)
    {
        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        return [
            'listing_product_id' => $listingProduct->getId(),
            'component'          => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'action_type'        => $action,
            'is_force'           => true,
            'tag'                => null,
            'additional_data'    => $this->getHelper('Data')->jsonEncode(
                [
                    'params' => $params
                ]
            ),
        ];
    }

    protected function getLogsAction($action)
    {
        switch ($action) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_RELIST_PRODUCT_ON_COMPONENT;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_PRODUCT_ON_COMPONENT;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action.');
    }

    //########################################
}
