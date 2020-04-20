<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database\DatabaseTableGrid
 */
class DatabaseTableGrid extends Table
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table\Grid $grid */
        $grid = $this->createBlock('ControlPanel_Tabs_Database_Table_Grid');
        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}
