<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\RemoveFilterFromGroup
 */
class RemoveFilterFromGroup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $filtersIds = $this->getRequest()->getParam('filters_ids');
        $groupId = $this->getRequest()->getParam('group_id');

        if (!is_array($filtersIds)) {
            $filtersIds = explode(',', $filtersIds);
        }

        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay_Motor_Group', $groupId);
        $model->removeFiltersByIds($filtersIds);

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}
