<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction\DeleteCategory
 */
class DeleteCategory extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $groupId = $this->getRequest()->getParam('group_id');
        $categoryId = $this->getRequest()->getParam('category_id');

        $category = $this->activeRecordFactory->getObject('Listing_Auto_Category')
            ->getCollection()
            ->addFieldToFilter('group_id', (int)$groupId)
            ->addFieldToFilter('category_id', (int)$categoryId)
            ->getFirstItem();

        if (!$category->getId()) {
            return;
        }

        $category->delete();

        if ($this->activeRecordFactory->getObject('Listing_Auto_Category_Group')->getResource()->isEmpty($groupId)) {
            $this->activeRecordFactory->getObject('Listing_Auto_Category_Group')->load($groupId)->delete();
        }
    }

    //########################################
}
