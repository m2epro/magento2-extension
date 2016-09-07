<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

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