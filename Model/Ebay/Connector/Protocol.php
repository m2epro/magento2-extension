<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector;

class Protocol extends \Ess\M2ePro\Model\Connector\Protocol
{
    // ########################################

    public function getComponent()
    {
        return 'Ebay';
    }

    public function getComponentVersion()
    {
        return 13;
    }

    // ########################################
}