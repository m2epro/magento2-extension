<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Processing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Processing\Single
 */
class Single extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_connector_command_pending_processing_single', 'id');
    }

    //########################################
}
