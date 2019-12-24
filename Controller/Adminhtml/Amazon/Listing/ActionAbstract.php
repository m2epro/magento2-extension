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

    protected function processConnector($action, array $params = [])
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return 'You should select Products';
        }

        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        $listingsProductsIds = explode(',', $listingsProductsIds);

        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Product_Dispatcher');
        $result = (int)$dispatcherObject->process($action, $listingsProductsIds, $params);
        $actionId = (int)$dispatcherObject->getLogsActionId();

        $listingProductObject = $this->amazonFactory
            ->getObjectLoaded('Listing\Product', $listingsProductsIds[0], null, false);

        $isProcessingItems = false;
        if ($listingProductObject !== null) {
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
