<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Actual
 */
class Actual extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelSummaryInfo');
        $this->setTemplate('control_panel/info/actual.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $this->systemName = $this->getHelper('Client')->getSystem();
        $this->systemTime = $this->getHelper('Data')->getCurrentGmtDate();
        // ---------------------------------------

        $this->magentoInfo = $this->__(ucwords($this->getHelper('Magento')->getEditionName())) .
            ' (' . $this->getHelper('Magento')->getVersion() . ')';

        // ---------------------------------------
        $this->publicVersion = $this->getHelper('Module')->getPublicVersion();
        $this->setupVersion  = $this->getHelper('Module')->getSetupVersion();
        $this->moduleEnvironment = $this->getHelper('Module')->getEnvironment();
        // ---------------------------------------

        // ---------------------------------------
        $this->maintenanceMode = $this->getHelper('Module_Maintenance')->isEnabled();
        $this->coreResourceVersion = $this->getHelper('Module')->getSchemaVersion();
        $this->coreResourceDataVersion = $this->getHelper('Module')->getDataVersion();
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
        $this->mySqlPrefix = $this->getHelper('Magento')->getDatabaseTablesPrefix();
        empty($this->mySqlPrefix) && $this->mySqlPrefix = $this->__('disabled');
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################
}
