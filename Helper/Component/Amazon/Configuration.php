<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

/**
 * Class \Ess\M2ePro\Helper\Component\Amazon\Configuration
 */
class Configuration extends \Ess\M2ePro\Helper\AbstractHelper
{
    const CONFIG_GROUP = '/amazon/configuration/';

    //########################################

    public function getBusinessMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'business_mode'
        );
    }

    public function isEnabledBusinessMode()
    {
        return $this->getBusinessMode() == 1;
    }

    //########################################

    public function setConfigValues(array $values)
    {
        if (isset($values['business_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'business_mode',
                $values['business_mode']
            );
        }
    }

    //########################################
}
