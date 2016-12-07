<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Actual extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelSummaryInfo');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/actual.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->magentoInfo = $this->__(ucwords($this->getHelper('Magento')->getEditionName())) .
            ' (' . $this->getHelper('Magento')->getVersion() . ')';

        // ---------------------------------------
        $this->publicVersion = $this->getHelper('Module')->getPublicVersion();
        $this->setupVersion  = $this->getHelper('Module')->getSetupVersion();
        $this->filesVersion  = $this->getHelper('Module')->getFilesVersion();
        // ---------------------------------------

        // ---------------------------------------
        $this->phpVersion = $this->getHelper('Client')->getPhpVersion();
        $this->phpApi = $this->getHelper('Client')->getPhpApiName();
        // ---------------------------------------

        // ---------------------------------------
        $this->memoryLimit = $this->getHelper('Client')->getMemoryLimit(true);
        $this->maxExecutionTime = ini_get('max_execution_time');
        // ---------------------------------------

        // ---------------------------------------
        $this->mySqlVersion = $this->getHelper('Client')->getMysqlVersion();
        $this->mySqlDatabaseName = $this->getHelper('Magento')->getDatabaseName();
        // ---------------------------------------

        // ---------------------------------------
        $this->cronLastRunTime = 'N/A';
        $this->cronIsNotWorking = false;
        $this->cronCurrentRunner = ucfirst($this->getHelper('Module\Cron')->getRunner());

        $cronLastRunTime = $this->getHelper('Module\Cron')->getLastRun();

        if (!is_null($cronLastRunTime)) {
            $this->cronLastRunTime = $cronLastRunTime;
            $this->cronIsNotWorking = $this->getHelper('Module\Cron')->isLastRunMoreThan(12,true);
        }
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################
}