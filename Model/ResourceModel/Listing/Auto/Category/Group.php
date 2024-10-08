<?php

namespace Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category;

class Group extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    public const ADDING_MODE_FIELD = 'adding_mode';
    public const DELETING_MODE_FIELD = 'deleting_mode';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_LISTING_AUTO_CATEGORY_GROUP,
            'id'
        );
    }

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
        $collection->getSelect()->where('main_table.group_id IN (' . implode(',', $groupIds) . ')');

        $data = [];

        foreach ($collection as $item) {
            $data[$item->getData('category_id')] = [
                'id' => $item->getData('group_id'),
                'title' => $item->getData('group_title'),
            ];
        }

        return $data;
    }

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
}
