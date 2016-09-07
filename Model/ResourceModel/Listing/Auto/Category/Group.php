<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category;

class Group extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_auto_category_group', 'id');
    }

    //########################################

    public function getCategoriesFromOtherGroups($listingId, $groupId = NULL)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group\Collection $groupCollection */
        $groupCollection = $this->activeRecordFactory->getObject('Listing\Auto\Category\Group')->getCollection();
        $groupCollection->addFieldToFilter('main_table.listing_id', (int)$listingId);

        if ($groupId) {
            $groupCollection->addFieldToFilter('main_table.id', array('neq' => (int)$groupId));
        }

        $groupIds = $groupCollection->getAllIds();
        if (count($groupIds) == 0) {
            return array();
        }

        $collection = $this->activeRecordFactory->getObject('Listing\Auto\Category')->getCollection();
        $collection->getSelect()->joinInner(
            array('melacg' => $this->getMainTable()),
            'main_table.group_id = melacg.id',
            array('group_title' => 'title')
        );
        $collection->getSelect()->where('main_table.group_id IN ('.implode(',',$groupIds).')');

        $data = array();

        foreach ($collection as $item) {
            $data[$item->getData('category_id')] = array(
                'id' => $item->getData('group_id'),
                'title' => $item->getData('group_title')
            );
        }

        return $data;
    }

    //########################################

    public function isEmpty($groupId)
    {
        $autoCategoryTable = $this->activeRecordFactory->getObject('Listing\Auto\Category')->getResource()
            ->getMainTable();
        $select = $this->getConnection()
            ->select()
            ->from(
                array('mlac' => $autoCategoryTable)
            )
            ->where('mlac.group_id = ?', $groupId);
        $result = $this->getConnection()->fetchAll($select);

        return count($result) === 0;
    }

    //########################################
}