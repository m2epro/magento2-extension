<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Request\Pending;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Request\Pending\Single
 */
class Single extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    // ########################################

    public function _construct()
    {
        $this->_init('m2epro_request_pending_single', 'id');
    }

    // ########################################

    public function getComponentsInProgress()
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), new \Zend_Db_Expr('DISTINCT `component`'))
            ->where('is_completed = ?', 0)
            ->distinct(true);

        return $this->getConnection()->fetchCol($select);
    }

    // ########################################
}
