<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride;

class Assign extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $templateId = $this->getRequest()->getParam('template_id');

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
                        'Shipping Override Policy cannot be assigned to some Products
                         because the Products are in Action'). '</p>'
            );
        }

        if (!empty($productsIdsLocked)) {
            $messages[] = array(
                'type' => 'success',
                'text' => $this->__('Shipping Override Policy was successfully assigned.')
            );

            $this->setShippingOverrideTemplateForProducts($productsIdsLocked, $templateId);
            $this->runProcessorForParents($productsIdsLocked);

            /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride $template */
            $template = $this->activeRecordFactory->getObjectLoaded('Amazon\Template\ShippingOverride', $templateId);
            $template->setSynchStatusNeed($template->getDataSnapshot(),array());
        }

        return $this->getResponse()->setBody(json_encode(array(
            'messages' => $messages
        )));
    }
}