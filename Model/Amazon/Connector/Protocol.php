<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector;

/**
 * Class Protocol
 * @package Ess\M2ePro\Model\Amazon\Connector
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
