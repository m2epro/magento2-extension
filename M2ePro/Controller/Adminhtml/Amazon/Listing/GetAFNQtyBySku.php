<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\GetAFNQtyBySku
 */
class GetAFNQtyBySku extends Main
{
    public function execute()
    {
        $accountId = $this->getRequest()->getParam('account_id');
        $skus = $this->getRequest()->getParam('skus');

        if (empty($skus) || empty($accountId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        if (!is_array($skus)) {
            $skus = explode(',', $skus);
        }

        /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'inventory',
            'get',
            'qtyAfnItems',
            [
                'items' => $skus,
                'only_realtime' => true
            ],
            null,
            $accountId
        );

        $dispatcherObject->process($connectorObj);

        $this->setJsonContent($connectorObj->getResponseData());
        return $this->getResult();
    }
}
