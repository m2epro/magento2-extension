<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class GetCategoriesByAsin extends Main
{
    public function execute()
    {
        $asin = $this->getRequest()->getParam('asin');
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($asin)) {
            return $this->getResponse()->setBody('You should select one or more Products');
        }

        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('product','search','categoriesByAsin',
            array('item' => $asin,
                'only_realtime' => true),
            null,
            $listingProduct->getAccount()->getId());

        $dispatcherObject->process($connectorObj);

        $categoriesData = $connectorObj->getResponseData();

        $this->setJsonContent([
            'data' => empty($categoriesData['categories']) ? '' : $categoriesData['categories']
        ]);

        return $this->getResult();
    }
}