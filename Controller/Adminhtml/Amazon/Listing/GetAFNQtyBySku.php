<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

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
        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('inventory','get','qtyAfnItems',
            array(
                'items' => $skus,
                'only_realtime' => true
            ),
            null,
            $accountId
        );

        $dispatcherObject->process($connectorObj);

        $this->setJsonContent($connectorObj->getResponseData());
        return $this->getResult();
    }
}