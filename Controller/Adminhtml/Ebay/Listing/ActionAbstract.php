<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
 */
abstract class ActionAbstract extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    //########################################

    protected function processConnector($action, array $params = [])
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return 'You should select Products';
        }

        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;
        $params['is_realtime'] = (bool)$this->getHelper('Data')->jsonDecode(
            $this->getRequest()->getParam('is_realtime')
        );

        $listingsProductsIds = explode(',', $listingsProductsIds);

        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Item_Dispatcher');
        $result = (int)$dispatcherObject->process($action, $listingsProductsIds, $params);
        $actionId = (int)$dispatcherObject->getLogsActionId();

        if ($result == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            return ['result'=>'error','action_id'=>$actionId];
        }

        if ($result == \Ess\M2ePro\Helper\Data::STATUS_WARNING) {
            return ['result'=>'warning','action_id'=>$actionId];
        }

        if ($result == \Ess\M2ePro\Helper\Data::STATUS_SUCCESS) {
            return ['result'=>'success','action_id'=>$actionId];
        }

        return ['result'=>'error','action_id'=>$actionId];
    }

    //########################################
}
