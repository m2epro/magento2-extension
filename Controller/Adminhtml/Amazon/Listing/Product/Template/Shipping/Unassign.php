<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping;

class Unassign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $shippingMode = $this->getRequest()->getParam('shipping_mode');

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $messages = array();
        $productsIdsLocked = $this->filterLockedProducts($productsIds);

        if (count($productsIdsLocked) < count($productsIds)) {
            $messages[] = array(
                'type' => 'warning',
                'text' => '<p>' . $this->__(
                        'Shipping Policy cannot be unassigned from some Products
                         because the Products are in Action'). '</p>'
            );
        }

        if (!empty($productsIdsLocked)) {
            $messages[] = array(
                'type' => 'success',
                'text' => $this->__('Shipping Policy was successfully unassigned.')
            );

            $this->setShippingTemplateForProducts($productsIdsLocked, NULL, $shippingMode);
            $this->runProcessorForParents($productsIdsLocked);
        }

        $this->setJsonContent(['messages' => $messages]);

        return $this->getResult();
    }
}