<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Fulfillment;

class SwitchToAFN extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Fulfillment
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            return $this->getResponse()->setBody('ERROR: Empty Product ID!');
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $listingProducts = array();
        foreach ($productsIds as $listingProductId) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');
            $configurator->reset();
            $configurator->allowQty();

            $listingProduct->setActionConfigurator($configurator);
            $listingProducts[] = $listingProduct;
        }

        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;
        $params['switch_to'] = \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_AFN;
        $action = \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Product\Dispatcher');
        $result = (int)$dispatcherObject->process($action, $listingProducts, $params);

        $this->setJsonContent([
            'messages' => array($this->getSwitchFulfillmentResultMessage($result))
        ]);

        return $this->getResult();
    }
}