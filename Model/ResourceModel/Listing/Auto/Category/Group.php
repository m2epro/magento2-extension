<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group
 */
class Group extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_auto_category_group', 'id');
    }

    //########################################

    public function getCategoriesFromOtherGroups($listingId, $groupId = null)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group\Collection $groupCollection */
        $groupCollection = $this->activeRecordFactory->getObject('Listing_Auto_Category_Group')->getCollection();
        $groupCollection->addFieldToFilter('main_table.listing_id', (int)$listingId);

        if ($groupId) {
            $groupCollection->addFieldToFilter('main_table.id', ['neq' => (int)$groupId]);
        }

        $groupIds = $groupCollection->getAllIds();
        if (count($groupIds) == 0) {
            return [];
        }

        $collection = $this->activeRecordFactory->getObject('Listing_Auto_Category')->getCollection();
        $collection->getSelect()->joinInner(
            ['melacg' => $this->getMainTable()],
            'main_table.group_id = melacg.id',
            ['group_title' => 'title']
        );
        $collection->getSelect()->where('main_table.group_id IN ('.implode(',', $groupIds).')');

        $data = [];

        foreach ($collection as $item) {
            $data[$item->getData('category_id')] = [
                'id' => $item->getData('group_id'),
                'title' => $item->getData('group_title')
            ];
        }

        return $data;
    }

    //########################################

    public function isEmpty($groupId)
    {
        $autoCategoryTable = $this->activeRecordFactory->getObject('Listing_Auto_Category')->getResource()
            ->getMainTable();
        $select = $this->getConnection()
            ->select()
            ->from(
                ['mlac' => $autoCategoryTable]
            )
            ->where('mlac.group_id = ?', $groupId);
        $result = $this->getConnection()->fetchAll($select);

        return count($result) === 0;
    }

    //########################################
}
