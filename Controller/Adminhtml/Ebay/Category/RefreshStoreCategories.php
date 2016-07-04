<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class RefreshStoreCategories extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $accountId = (int)$this->getRequest()->getParam('account_id');

        $this->ebayFactory->getObjectLoaded('Account', $accountId)->getChildObject()->updateEbayStoreInfo();

        return $this->getResult();
    }

    //########################################
}