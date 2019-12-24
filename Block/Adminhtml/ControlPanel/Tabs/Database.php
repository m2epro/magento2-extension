<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database
 */
class Database extends AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelDatabase');

        $this->_controller = 'adminhtml_controlPanel_tabs_database';
        // ---------------------------------------

        $this->setTemplate('magento/grid/container/only_content.phtml');
    }
}
