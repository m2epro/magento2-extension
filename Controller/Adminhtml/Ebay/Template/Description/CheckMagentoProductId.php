<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

class CheckMagentoProductId extends Description
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id', -1);

        $this->setJsonContent([
            'result' => $this->isMagentoProductExists($productId)
        ]);

        return $this->getResult();
    }
}