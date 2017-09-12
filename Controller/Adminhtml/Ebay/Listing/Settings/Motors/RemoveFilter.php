<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class RemoveFilter extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $filtersIds = $this->getRequest()->getParam('filters_ids');

        if (!is_array($filtersIds)) {
            $filtersIds = explode(',', $filtersIds);
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Filter\Collection $filters */
        $filters = $this->activeRecordFactory->getObject('Ebay\Motor\Filter')->getCollection()
            ->addFieldToFilter('id', array('in' => $filtersIds));

        foreach ($filters->getItems() as $filter) {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('m2epro_ebay_motor_filter_to_group');

            $select = $connection->select();
            $select->from(array('emftg' => $table), array('group_id'))
                ->where('filter_id IN (?)', $filter->getId());

            $groupIds = $connection->fetchCol($select);

            $filter->delete();

            foreach ($groupIds as $groupId) {
                /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $group */
                $group = $this->activeRecordFactory->getObjectLoaded('Ebay\Motor\Group', $groupId);

                if (count($group->getFiltersIds()) === 0) {
                    $group->delete();
                }
            }
        }

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}