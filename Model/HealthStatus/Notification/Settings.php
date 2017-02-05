<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Notification;

class Settings extends \Ess\M2ePro\Model\AbstractModel
{
    const MODE_DISABLED                    = 0;
    const MODE_EXTENSION_PAGES             = 1;
    const MODE_MAGENTO_PAGES               = 2;
    const MODE_MAGENTO_SYSTEM_NOTIFICATION = 3;
    const MODE_EMAIL                       = 4;

    /** @var \Ess\M2ePro\Model\Config\Manager\Module */
    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ){
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->moduleConfig  = $moduleConfig;
    }

    //########################################

    public function getMode()
    {
        return (int)$this->moduleConfig->getGroupValue('/health_status/notification/', 'mode');
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
        return $this->moduleConfig->getGroupValue('/health_status/notification/', 'email');
    }

    //----------------------------------------

    public function getLevel()
    {
        return $this->moduleConfig->getGroupValue('/health_status/notification/', 'level');
    }

    //########################################
}