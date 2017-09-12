<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

class ArchivedEntity extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_archived_entity', 'id');
    }

    //########################################

    public function retrieve($name, $originId)
    {
        $collection = $this->activeRecordFactory->getObject('ArchivedEntity')->getCollection();
        $collection->addFieldToFilter('name', $name)
                   ->addFieldToFilter('origin_id', (int)$originId)
                   ->setOrder(
                       $collection->getResource()->getIdFieldName(),
                       \Magento\Framework\Data\Collection::SORT_ORDER_DESC
                   );

        $collection->getSelect()->limit(1);
        $entity = $collection->getFirstItem();

        return $entity->getId() ? $entity : null;
    }

    //########################################
}