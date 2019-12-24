<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database\GetTableCellsPopupHtml
 */
class GetTableCellsPopupHtml extends Table
{
    public function execute()
    {
        $block = $this->createBlock('ControlPanel_Tabs_Database_Table_TableCellsPopup');
        $this->setAjaxContent($block->toHtml());
        return $this->getResult();
    }
}
