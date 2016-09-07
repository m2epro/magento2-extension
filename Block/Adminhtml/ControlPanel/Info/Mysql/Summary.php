<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

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