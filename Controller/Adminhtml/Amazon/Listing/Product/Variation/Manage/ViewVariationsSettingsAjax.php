<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class ViewVariationsSettingsAjax extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        $settings = $this->createBlock('Amazon\Listing\Product\Variation\Manage\Tabs\Settings\Form');
        $settings->setListingProduct($this->amazonFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setJsonContent([
            'error_icon' => count($settings->getMessages()) > 0 ? $settings->getMessagesType() : '',
            'html' => $settings->toHtml()
        ]);
        return $this->getResult();
    }
}