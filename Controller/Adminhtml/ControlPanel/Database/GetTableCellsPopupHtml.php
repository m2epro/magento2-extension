<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class GetTableCellsPopupHtml extends Table
{
    public function execute()
    {
        $block = $this->createBlock('ControlPanel\Tabs\Database\Table\TableCellsPopup');
        $this->setAjaxContent($block->toHtml());
        return $this->getResult();
    }
}