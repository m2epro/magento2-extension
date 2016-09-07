<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Config;

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