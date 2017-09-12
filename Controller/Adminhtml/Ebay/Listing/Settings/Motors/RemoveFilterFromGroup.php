<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

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

        $connection = $this->resourceConnection->getConnection();

        $filterGroupRelation = $this->resourceConnection->getTableName('m2epro_ebay_motor_filter_to_group');

        $connection->delete($filterGroupRelation, array(
            'filter_id in (?)' => $filtersIds,
            'group_id = ?' => $groupId,
        ));

        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay\Motor\Group', $groupId);

        if (count($model->getFiltersIds()) == 0) {
            $model->delete();
        }

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}