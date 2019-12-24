<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping\Unassign
 */
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

        $messages = [];
        $productsIdsLocked = $this->filterLockedProducts($productsIds);

        if (count($productsIdsLocked) < count($productsIds)) {
            $messages[] = [
                'type' => 'warning',
                'text' => '<p>' . $this->__(
                    'Shipping Policy cannot be unassigned from some Products
                         because the Products are in Action'
                ). '</p>'
            ];
        }

        if (!empty($productsIdsLocked)) {
            $messages[] = [
                'type' => 'success',
                'text' => $this->__('Shipping Policy was successfully unassigned.')
            ];

            $this->setShippingTemplateForProducts($productsIdsLocked, null, $shippingMode);
            $this->runProcessorForParents($productsIdsLocked);
        }

        $this->setJsonContent(['messages' => $messages]);

        return $this->getResult();
    }
}
