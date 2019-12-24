<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\EditSku
 */
class EditSku extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
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
        $value = $this->getRequest()->getParam('value');

        if (empty($productId) || empty($value)) {
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

        $oldSku = $listingProduct->getChildObject()->getData('sku');
        if ($oldSku === $value) {
            $this->setJsonContent([
                'result' => true,
                'message' => ''
            ]);

            return $this->getResult();
        }

        try {
            /** @var Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
            $configurator->reset();
            $configurator->allowDetails();

            $listingProduct->setActionConfigurator($configurator);

            $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

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

    //########################################
}
