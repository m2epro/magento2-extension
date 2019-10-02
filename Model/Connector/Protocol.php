<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector;

/**
 * Class Protocol
 * @package Ess\M2ePro\Model\Connector
 */
abstract class Protocol extends \Ess\M2ePro\Model\AbstractModel
{
    // ########################################

    abstract public function getComponent();

    abstract public function getComponentVersion();

    // ########################################
}
