<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\RemoveGroup
 */
class RemoveGroup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $groupsIds = $this->getRequest()->getParam('groups_ids');

        if (!is_array($groupsIds)) {
            $groupsIds = explode(',', $groupsIds);
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Group\Collection $groups */
        $groups = $this->activeRecordFactory->getObject('Ebay_Motor_Group')->getCollection()
            ->addFieldToFilter('id', ['in' => $groupsIds]);

        foreach ($groups->getItems() as $group) {
            $group->delete();
        }

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}
