<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping;

class Assign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping
{
    public function execute()
    {
        $productsIds  = $this->getRequest()->getParam('products_ids');
        $templateId   = $this->getRequest()->getParam('template_id');
        $shippingMode = $this->getRequest()->getParam('shipping_mode');

        if (empty($productsIds) || empty($templateId)) {
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
                        'Shipping Policy cannot be assigned to some Products
                         because the Products are in Action'). '</p>'
            );
        }

        if (!empty($productsIdsLocked)) {
            $messages[] = array(
                'type' => 'success',
                'text' => $this->__('Shipping Policy was successfully assigned.')
            );

            $this->setShippingTemplateForProducts($productsIdsLocked, $templateId, $shippingMode);
            $this->runProcessorForParents($productsIdsLocked);

            if ($shippingMode == \Ess\M2ePro\Model\Amazon\Account::SHIPPING_MODE_OVERRIDE) {
                $template = $this->activeRecordFactory->getObjectLoaded(
                    'Amazon\Template\ShippingOverride', $templateId
                );
            } else {
                $template = $this->activeRecordFactory->getObjectLoaded(
                    'Amazon\Template\ShippingTemplate', $templateId
                );
            }

            $template->setSynchStatusNeed($template->getDataSnapshot(), array());
        }

        $this->setJsonContent(['messages' => $messages]);

        return $this->getResult();
    }
}