<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Protocol
 */
class Protocol extends \Ess\M2ePro\Model\Connector\Protocol
{
    // ########################################

    public function getComponent()
    {
        return 'Walmart';
    }

    public function getComponentVersion()
    {
        return 2;
    }

    // ########################################
}
