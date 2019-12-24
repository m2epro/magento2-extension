<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetRecent
 */
class GetRecent extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace');
        $accountId = $this->getRequest()->getParam('account');
        $categoryType = $this->getRequest()->getParam('category_type');
        $selectedCategory = $this->getRequest()->getParam('selected_category');

        if (in_array($categoryType, $this->getHelper('Component_Ebay_Category')->getEbayCategoryTypes())) {
            $categories = $this->getHelper('Component_Ebay_Category')->getRecent(
                $marketplaceId,
                $categoryType,
                $selectedCategory
            );
        } else {
            $categories = $this->getHelper('Component_Ebay_Category')->getRecent(
                $accountId,
                $categoryType,
                $selectedCategory
            );
        }

        $this->setJsonContent($categories);

        return $this->getResult();
    }

    //########################################
}
