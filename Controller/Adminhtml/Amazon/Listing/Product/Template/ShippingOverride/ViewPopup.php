<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride;

class ViewPopup extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride
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
                'text' => $this->__(
                    'The Shipping Override Policy was not assigned because the Products have In Action Status.'
                )
            );
        }

        if (empty($productsIdsLocked)) {
            $this->setJsonContent([
                'messages' => $messages
            ]);

            return $this->getResult();
        }

        $mainBlock = $this->createBlock('Amazon\Listing\Product\Template\ShippingOverride');
        if (!empty($messages)) {
            $mainBlock->setMessages($messages);
        }

        $this->setJsonContent([
            'html' => $mainBlock->toHtml(),
            'messages' => $messages,
            'products_ids' => implode(',', $productsIdsLocked)
        ]);

        return $this->getResult();
    }
}