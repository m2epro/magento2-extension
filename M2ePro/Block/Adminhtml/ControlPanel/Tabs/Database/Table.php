<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table
 */
class Table extends AbstractContainer
{
    private $cookieManager;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->cookieManager = $cookieManager;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelDatabaseTable');
        $this->_controller = 'adminhtml_controlPanel_tabs_database_table';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $tableName = $this->getRequest()->getParam('table');
        $component = $this->getRequest()->getParam('component');

        $title = $this->__('Manage Table "%table_name%"', $tableName);
        if ($this->isMergeModeEnabled() && $component &&
            $this->getHelper('Module_Database_Structure')->isTableHorizontalParent($tableName)) {
            $title .= " [merged {$component} data]";
        }

        $this->pageConfig->getTitle()->prepend($title);
        $this->_headerText = $this->__($title);
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getHelper('View\ControlPanel')->getPageDatabaseTabUrl();
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => "window.open('{$url}','_blank')",
            'class'     => 'back'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/controlPanel_tools/magento', ['action' => 'clearMagentoCache']);
        $this->addButton('additional-actions', [
            'label'      => $this->__('Additional Actions'),
            'onclick'    => '',
            'class'      => 'action-secondary',
            'sort_order' => 100,
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options'    => [
                'clear-cache' => [
                    'label'   => $this->__('Flush Cache'),
                    'onclick' => "window.open('{$url}', '_blank');"
                ],
            ],
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/truncateTables', ['tables' => $tableName]);
        $this->addButton('delete_all', [
            'label'      => $this->__('Truncate Table'),
            'onclick'    => 'deleteConfirm(\'Are you sure?\', \''.$url.'\')',
            'class'      => 'action-error',
            'sort_order' => 80,
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('add_row', [
            'label'      => $this->__('Append Row'),
            'onclick'    => 'ControlPanelDatabaseGridObj.openTableCellsPopup(\'add\')',
            'class'      => 'action-success',
            'sort_order' => 90,
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $helper = $this->getHelper('Module_Database_Structure');

        if ($helper->isTableHorizontalChild($tableName) ||
            ($helper->isTableHorizontalParent($tableName) && $this->isMergeModeEnabled() && $component)) {
            $labelAdd = $this->isMergeModeEnabled() ? 'disable' : 'enable';

            $this->addButton('merge_mode', [
                'label'      => $this->__("Join Full Collection [{$labelAdd}]"),
                'onclick'    => 'ControlPanelDatabaseGridObj.switchMergeMode()',
                'class'      => !$this->isMergeModeEnabled() ? 'action-success' : 'action-warning',
                'sort_order' => 70,
            ]);
        }
        // ---------------------------------------
    }

    //########################################

    public function isMergeModeEnabled()
    {
        $key = \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table\Grid::MERGE_MODE_COOKIE_KEY;
        return (bool)$this->cookieManager->getCookie($key);
    }

    //########################################
}
