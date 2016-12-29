<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping;

class ViewPopup extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping
{
    public function execute()
    {
        $productsIds  = $this->getRequest()->getParam('products_ids');
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
                'text' => $this->__(
                    'The Shipping Policy was not assigned because the Products have In Action Status.'
                )
            );
        }

        if (empty($productsIdsLocked)) {
            $this->setJsonContent([
                'messages' => $messages
            ]);

            return $this->getResult();
        }

        $blockName = ($shippingMode == \Ess\M2ePro\Model\Amazon\Account::SHIPPING_MODE_OVERRIDE)
            ? 'Amazon\Listing\Product\Template\ShippingOverride'
            : 'Amazon\Listing\Product\Template\ShippingTemplate';

        $mainBlock = $this->createBlock($blockName);
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