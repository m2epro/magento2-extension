<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Request\Pending;

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
            ->from($this->getTable('m2epro_request_pending_partial_data'), 'data')
            ->where('request_pending_partial_id = ?', $requestPendingPartial->getId())
            ->where('part_number = ?', $partNumber);

        $resultData = $this->getConnection()->fetchCol($select);
        $resultData = reset($resultData);

        return !empty($resultData) ? $this->getHelper('Data')->jsonDecode($resultData) : NULL;
    }

    public function addResultData(\Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial,
                                  $partNumber,
                                  array $data)
    {
        $this->getConnection()->insert(
            $this->getTable('m2epro_request_pending_partial_data'),
            array(
                'request_pending_partial_id' => $requestPendingPartial->getId(),
                'part_number' => $partNumber,
                'data' => $this->getHelper('Data')->jsonEncode($data),
            )
        );
    }

    public function deleteResultData(\Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial)
    {
        $this->getConnection()->delete(
            $this->getTable('m2epro_request_pending_partial_data'),
            array('request_pending_partial_id = ?' => $requestPendingPartial->getId())
        );
    }

    // ########################################
}