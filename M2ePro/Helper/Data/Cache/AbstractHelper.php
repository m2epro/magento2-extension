<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data\Cache;

/**
 * Class \Ess\M2ePro\Helper\Data\Cache\AbstractHelper
 */
abstract class AbstractHelper extends \Ess\M2ePro\Helper\AbstractHelper
{
    //########################################

    abstract public function getValue($key);

    abstract public function setValue($key, $value, array $tags = [], $lifetime = null);

    //########################################

    abstract public function removeValue($key);

    abstract public function removeTagValues($tag);

    abstract public function removeAllValues();

    //########################################
}
