<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride;

class Unassign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

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
                        'Shipping Override Policy cannot be unassigned from some Products
                         because the Products are in Action'). '</p>'
            );
        }

        if (!empty($productsIdsLocked)) {
            $messages[] = array(
                'type' => 'success',
                'text' => $this->__('Shipping Override Policy was successfully unassigned.')
            );

            $this->setShippingOverrideTemplateForProducts($productsIdsLocked, NULL);
            $this->runProcessorForParents($productsIdsLocked);
        }

        return $this->getResponse()->setBody(json_encode(array(
            'messages' => $messages
        )));
    }
}