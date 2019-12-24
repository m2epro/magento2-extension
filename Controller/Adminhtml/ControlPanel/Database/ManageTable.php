<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database\ManageTable
 */
class ManageTable extends Table
{
    public function execute()
    {
        $this->init();
        $table = $this->getRequest()->getParam('table');

        if ($table === null) {
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageDatabaseTabUrl());
        }

        $this->addContent($this->createBlock('ControlPanel_Tabs_Database_Table'));
        return $this->getResultPage();
    }
}
