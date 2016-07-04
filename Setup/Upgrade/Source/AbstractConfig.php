<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\Source;

abstract class AbstractConfig
{
    //########################################

    /**
     * @return array
     */
    abstract public function getFeaturesList();

    //########################################
}