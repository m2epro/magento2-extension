<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class DatabaseTableGrid extends Table
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table\Grid $grid */
        $grid = $this->createBlock('ControlPanel\Tabs\Database\Table\Grid');
        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}