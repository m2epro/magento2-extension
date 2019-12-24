<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\EditIdentifier
 */
class EditIdentifier extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $type = $this->getRequest()->getParam('type');
        $value = $this->getRequest()->getParam('value');

        $allowedTypes = ['gtin', 'upc', 'ean', 'isbn'];

        if (empty($productId) || empty($type) || empty($value) || !in_array($type, $allowedTypes)) {
            $this->setJsonContent([
                'result' => false,
                'message' => $this->__('Wrong parameters.')
            ]);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $productId);

        if (!$listingProduct->getId()) {
            $this->setJsonContent([
                'result' => false,
                'message' => $this->__('Listing product does not exist.')
            ]);

            return $this->getResult();
        }

        $oldIdentifier = $listingProduct->getChildObject()->getData($type);
        if ($oldIdentifier === $value) {
            $this->setJsonContent([
                'result' => true,
                'message' => ''
            ]);

            return $this->getResult();
        }

        try {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');

            $configurator->reset();
            $configurator->allowDetails();

            $listingProduct->setActionConfigurator($configurator);

            $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;
            $params['changed_identifier'] = [
                'type'  => $type,
                'value' => $value,
            ];

            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Product_Dispatcher');
            $dispatcherObject->process(\Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, [$listingProduct], $params);
        } catch (\Exception $exception) {
            $this->setJsonContent([
                'result' => false,
                'message' => $exception->getMessage()
            ]);

            return $this->getResult();
        }

        $this->setJsonContent([
            'result' => true,
            'message' => ''
        ]);

        return $this->getResult();
    }

    private function getLogsAction($action)
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
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action.');
    }

    //########################################
}
