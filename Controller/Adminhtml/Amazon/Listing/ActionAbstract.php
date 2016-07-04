<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

abstract class ActionAbstract extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    //########################################

    protected function processConnector($action, array $params = array())
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return 'You should select Products';
        }

        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        $listingsProductsIds = explode(',', $listingsProductsIds);

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Product\Dispatcher');
        $result = (int)$dispatcherObject->process($action, $listingsProductsIds, $params);
        $actionId = (int)$dispatcherObject->getLogsActionId();

        $listingProductObject = $this->amazonFactory
            ->getObjectLoaded('Listing\Product', $listingsProductsIds[0], NULL, false);

        $isProcessingItems = false;
        if (!is_null($listingProductObject)) {
            $isProcessingItems = (bool)$listingProductObject->getListing()
                ->isSetProcessingLock('products_in_action');
        }

        if ($result == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            return ['result'=>'error','action_id'=>$actionId,'is_processing_items'=>$isProcessingItems];
        }

        if ($result == \Ess\M2ePro\Helper\Data::STATUS_WARNING) {
            return ['result'=>'warning','action_id'=>$actionId,'is_processing_items'=>$isProcessingItems];
        }

        if ($result == \Ess\M2ePro\Helper\Data::STATUS_SUCCESS) {
            return ['result'=>'success','action_id'=>$actionId,'is_processing_items'=>$isProcessingItems];
        }

        return ['result'=>'error','action_id'=>$actionId,'is_processing_items'=>$isProcessingItems];
    }

    //########################################
}