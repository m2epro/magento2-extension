<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Notification;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Notification\Settings
 */
class Settings extends \Ess\M2ePro\Model\AbstractModel
{
    const MODE_DISABLED                    = 0;
    const MODE_EXTENSION_PAGES             = 1;
    const MODE_MAGENTO_PAGES               = 2;
    const MODE_MAGENTO_SYSTEM_NOTIFICATION = 3;
    const MODE_EMAIL                       = 4;

    //########################################

    public function getMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue('/health_status/notification/', 'mode');
    }

    public function isModeDisabled()
    {
        return $this->getMode() == self::MODE_DISABLED;
    }

    public function isModeExtensionPages()
    {
        return $this->getMode() == self::MODE_EXTENSION_PAGES;
    }

    public function isModeMagentoPages()
    {
        return $this->getMode() == self::MODE_MAGENTO_PAGES;
    }

    public function isModeMagentoSystemNotification()
    {
        return $this->getMode() == self::MODE_MAGENTO_SYSTEM_NOTIFICATION;
    }

    public function isModeEmail()
    {
        return $this->getMode() == self::MODE_EMAIL;
    }

    //----------------------------------------

    public function getEmail()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/health_status/notification/', 'email');
    }

    //----------------------------------------

    public function getLevel()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/health_status/notification/', 'level');
    }

    //########################################
}
