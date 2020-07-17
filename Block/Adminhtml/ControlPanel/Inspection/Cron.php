<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\Cron
 */
class Cron extends AbstractInspection
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInspectionCron');
        $this->setTemplate('control_panel/inspection/cron.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $modConfig = $this->getHelper('Module')->getConfig();

        $this->cronLastRunTime = 'N/A';
        $this->cronIsNotWorking = false;
        $this->cronCurrentRunner = ucwords(str_replace('_', ' ', $this->getHelper('Module\Cron')->getRunner()));
        $this->cronServiceAuthKey = $modConfig->getGroupValue('/cron/service/', 'auth_key');

        $cronLastRunTime = $this->getHelper('Module\Cron')->getLastRun();
        if ($cronLastRunTime !== null) {
            $this->cronLastRunTime = $cronLastRunTime;
            $this->cronIsNotWorking = $this->getHelper('Module\Cron')->isLastRunMoreThan(1, true);
        }

        $this->isMagentoCronDisabled    = (bool)(int)$modConfig->getGroupValue('/cron/magento/', 'disabled');
        $this->isControllerCronDisabled = (bool)(int)$modConfig->getGroupValue('/cron/service_controller/', 'disabled');
        $this->isPubCronDisabled        = (bool)(int)$modConfig->getGroupValue('/cron/service_pub/', 'disabled');

        return parent::_beforeToHtml();
    }

    //########################################

    public function isShownServiceDescriptionMessage()
    {
        if (!$this->getData('is_support_mode')) {
            return false;
        }

        if ($this->getHelper('Module\Cron')->isRunnerService() && !$this->cronIsNotWorking) {
            return true;
        }

        return false;
    }

    //########################################
}
