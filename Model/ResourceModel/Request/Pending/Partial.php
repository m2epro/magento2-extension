<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Request\Pending;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Request\Pending\Partial
 */
class Partial extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    // ########################################

    public function _construct()
    {
        $this->_init('m2epro_request_pending_partial', 'id');
    }

    // ########################################

    public function getResultData(\Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial, $partNumber)
    {
        $select = $this->getConnection()->select()
            ->from(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_request_pending_partial_data'),
                'data'
            )
            ->where('request_pending_partial_id = ?', $requestPendingPartial->getId())
            ->where('part_number = ?', $partNumber);

        $resultData = $this->getConnection()->fetchCol($select);
        $resultData = reset($resultData);

        return !empty($resultData) ? $this->getHelper('Data')->jsonDecode($resultData) : null;
    }

    public function addResultData(
        \Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial,
        $partNumber,
        array $data
    ) {
        $this->getConnection()->insert(
            $this->getHelper('Module_Database_Structure')
                 ->getTableNameWithPrefix('m2epro_request_pending_partial_data'),
            [
                'request_pending_partial_id' => $requestPendingPartial->getId(),
                'part_number' => $partNumber,
                'data' => $this->getHelper('Data')->jsonEncode($data),
            ]
        );
    }

    public function deleteResultData(\Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial)
    {
        $this->getConnection()->delete(
            $this->getHelper('Module_Database_Structure')
                 ->getTableNameWithPrefix('m2epro_request_pending_partial_data'),
            ['request_pending_partial_id = ?' => $requestPendingPartial->getId()]
        );
    }

    // ########################################
}
