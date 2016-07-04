<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data\Cache;

abstract class AbstractHelper extends \Ess\M2ePro\Helper\AbstractHelper
{
    //########################################

    abstract public function getValue($key);

    abstract public function setValue($key, $value, array $tags = array(), $lifetime = null);

    //########################################

    abstract public function removeValue($key);

    abstract public function removeTagValues($tag);

    abstract public function removeAllValues();

    //########################################
}