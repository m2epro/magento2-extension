<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql\Info
 */
class Info extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelAboutMysqlInfo');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/mysql/info.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->mySqlDatabaseName = $this->getHelper('Magento')->getDatabaseName();
        $this->mySqlVersion = $this->getHelper('Client')->getMysqlVersion();
        $this->mySqlApi = $this->getHelper('Client')->getMysqlApiName();
        $this->mySqlPrefix = $this->getHelper('Magento')->getDatabaseTablesPrefix();
        $this->mySqlSettings = $this->getHelper('Client')->getMysqlSettings();

        return parent::_beforeToHtml();
    }

    //########################################
}
