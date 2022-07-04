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
        $block = $this->getLayout()
                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table\TableCellsPopup::class);
        $this->setAjaxContent($block->toHtml());
        return $this->getResult();
    }
}
