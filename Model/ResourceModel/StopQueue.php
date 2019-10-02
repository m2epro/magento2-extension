<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

/**
 * Class StopQueue
 * @package Ess\M2ePro\Model\ResourceModel
 */
class StopQueue extends ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_stop_queue', 'id');
    }

    //########################################
}
