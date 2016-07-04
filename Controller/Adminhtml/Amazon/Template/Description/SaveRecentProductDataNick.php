<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class SaveRecentProductDataNick extends Description
{
    //########################################

    public function execute()
    {
        $marketplaceId   = $this->getRequest()->getPost('marketplace_id');
        $productDataNick = $this->getRequest()->getPost('product_data_nick');

        if (!$marketplaceId || !$productDataNick) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        $this->getHelper('Component\Amazon\ProductData')->addRecent($marketplaceId, $productDataNick);
        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }

    //########################################
}