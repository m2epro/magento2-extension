<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Upgrade\Entity;

/**
 * Class \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
 */
abstract class AbstractConfig
{
    //########################################

    /**
     * @return array
     */
    abstract public function getFeaturesList();

    //########################################
}
