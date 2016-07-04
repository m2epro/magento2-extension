<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector;

abstract class Protocol extends \Ess\M2ePro\Model\AbstractModel
{
    // ########################################

    abstract public function getComponent();

    abstract public function getComponentVersion();

    // ########################################
}