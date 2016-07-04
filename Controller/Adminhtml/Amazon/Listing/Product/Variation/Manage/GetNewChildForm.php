<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class GetNewChildForm extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        $form = $this->createBlock('Amazon\Listing\Product\Variation\Manage\Tabs\Variations\NewChild\Form');
        $form->setListingProduct($this->amazonFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setAjaxContent($form);

        return $this->getResult();
    }
}