<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

/**
 * Class DeleteCategoryGroup
 * @package Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
 */
class DeleteCategoryGroup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $groupId = $this->getRequest()->getParam('group_id');

        $this->activeRecordFactory->getObject('Listing_Auto_Category_Group')
            ->load($groupId)
            ->delete();
    }

    //########################################
}
