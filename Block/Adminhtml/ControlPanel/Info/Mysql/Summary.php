<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql\Summary
 */
class Summary extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelAboutMysqlSummary');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/mysql/summary.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->mySqlTotal = $this->getHelper('Client')->getMysqlTotals();

        return parent::_beforeToHtml();
    }

    //########################################
}
