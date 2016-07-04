<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Processing\Action;

class Item extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    // ########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_processing_action_item', 'id');
    }

    // ########################################

    public function markAsSkipped(array $relatedIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            array('is_skipped' => 1),
            array('related_id IN (?)' => $relatedIds)
        );
    }

    // ########################################

    public function deleteByAction(\Ess\M2ePro\Model\Ebay\Processing\Action $action)
    {
        return $this->getConnection()->delete($this->getMainTable(), array('action_id = ?' => $action->getId()));
    }

    // ########################################
}