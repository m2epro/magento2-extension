<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class DeleteCategoryGroup extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $groupId = $this->getRequest()->getParam('group_id');

        $this->activeRecordFactory->getObject('Listing\Auto\Category\Group')
            ->load($groupId)
            ->delete();
    }

    //########################################

}