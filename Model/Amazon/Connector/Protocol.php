<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Protocol
 */
class Protocol extends \Ess\M2ePro\Model\Connector\Protocol
{
    // ########################################

    public function getComponent()
    {
        return 'Amazon';
    }

    public function getComponentVersion()
    {
        return 8;
    }

    // ########################################
}
