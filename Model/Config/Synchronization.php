<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Config;

/**
 * Class \Ess\M2ePro\Model\Config\Synchronization
 */
class Synchronization extends AbstractModel
{
    //########################################

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Ess\M2ePro\Model\ResourceModel\Config\Synchronization');
    }

    //########################################
}
