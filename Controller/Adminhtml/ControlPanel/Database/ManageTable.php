<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class ManageTable extends Table
{
    /** @var \Ess\M2ePro\Helper\View\ControlPanel */
    protected $controlPanelHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\ControlPanel\Database\TableModelFactory $databaseTableFactory
    ) {
        parent::__construct($context, $databaseTableFactory);
        $this->controlPanelHelper = $controlPanelHelper;
    }

    public function execute()
    {
        $this->init();
        $table = $this->getRequest()->getParam('table');

        if ($table === null) {
            return $this->_redirect($this->controlPanelHelper->getPageDatabaseTabUrl());
        }

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table::class)
        );
        return $this->getResultPage();
    }
}
