<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class ManageTable extends Table
{
    public function execute()
    {
        $this->init();
        $table = $this->getRequest()->getParam('table');

        if (is_null($table)) {
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageDatabaseTabUrl());
        }

        $this->addContent($this->createBlock('ControlPanel\Tabs\Database\Table'));
        return $this->getResultPage();
    }
}