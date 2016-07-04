<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class GetRecent extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace');
        $accountId = $this->getRequest()->getParam('account');
        $categoryType = $this->getRequest()->getParam('category_type');
        $selectedCategory = $this->getRequest()->getParam('selected_category');

        if (in_array($categoryType, $this->getHelper('Component\Ebay\Category')->getEbayCategoryTypes())) {
            $categories = $this->getHelper('Component\Ebay\Category')->getRecent(
                $marketplaceId, $categoryType, $selectedCategory
            );
        } else {
            $categories = $this->getHelper('Component\Ebay\Category')->getRecent(
                $accountId, $categoryType, $selectedCategory
            );
        }

        $this->setJsonContent($categories);

        return $this->getResult();
    }

    //########################################
}