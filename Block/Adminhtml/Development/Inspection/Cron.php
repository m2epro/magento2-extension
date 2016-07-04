<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Development\Inspection;

class Cron extends AbstractInspection
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Ess_M2ePro::development/inspection/cron.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $moduleConfig = $this->getHelper('Module')->getConfig();

        $this->cronLastRunTime = 'N/A';
        $this->cronIsNotWorking = false;
        $this->cronCurrentRunner = ucfirst($this->getHelper('Module\Cron')->getRunner());
        $this->cronServiceAuthKey = $moduleConfig->getGroupValue('/cron/service/', 'auth_key');

        $baseDir = $this->getHelper('Client')->getBaseDirectory();
        $this->cronPhp = 'php -q '.$baseDir.'cron.php -mdefault 1';

        $baseUrl = $this->getHelper('Magento')->getBaseUrl();
        $this->cronGet = 'GET '.$baseUrl.'cron.php';

        $cronLastRunTime = $this->getHelper('Module\Cron')->getLastRun();
        if (!is_null($cronLastRunTime)) {
            $this->cronLastRunTime = $cronLastRunTime;
            $this->cronIsNotWorking = $this->getHelper('Module\Cron')->isLastRunMoreThan(12,true);
        }

        $serviceHostName = $moduleConfig->getGroupValue('/cron/service/', 'hostname');
        $this->cronServiceIp = gethostbyname($serviceHostName);

        $this->isMagentoCronDisabled = (bool)(int)$moduleConfig->getGroupValue('/cron/magento/','disabled');
        $this->isServiceCronDisabled = (bool)(int)$moduleConfig->getGroupValue('/cron/service/','disabled');

        return parent::_beforeToHtml();
    }

    //########################################

    public function isShownRecommendationsMessage()
    {
        if (!$this->getData('is_support_mode')) {
            return false;
        }

        if ($this->getHelper('Module\Cron')->isRunnerMagento()) {
            return true;
        }

        if ($this->getHelper('Module\Cron')->isRunnerService() && $this->cronIsNotWorking) {
            return true;
        }

        return false;
    }

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