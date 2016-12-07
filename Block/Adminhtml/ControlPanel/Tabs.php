<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs;
use \Ess\M2ePro\Helper\View\ControlPanel as HelperControlPanel;

class Tabs extends AbstractHorizontalTabs
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setDestElementId('control_panel_tab_container');
    }

    //########################################

    protected function _prepareLayout()
    {
        $activeTab = $this->getRequest()->getParam('tab');
        $allowedTabs = array(
            HelperControlPanel::TAB_OVERVIEW,
            HelperControlPanel::TAB_INSPECTION,
            HelperControlPanel::TAB_DATABASE,
            HelperControlPanel::TAB_VERSIONS_HISTORY,
            HelperControlPanel::TAB_TOOLS_GENERAL,
            HelperControlPanel::TAB_TOOLS_MODULE,
            HelperControlPanel::TAB_DEBUG,
        );

        if (!in_array($activeTab, $allowedTabs)) {
            $activeTab = HelperControlPanel::TAB_OVERVIEW;
        }

        // ---------------------------------------
        $params = array('label' => $this->helperFactory->getObject('Module\Translation')->__('Overview'));
        if ($activeTab == HelperControlPanel::TAB_OVERVIEW) {
            $params['content'] = $this->createBlock('ControlPanel\Tabs\Overview')->toHtml();
        } else {
            $params['url'] = $this->getUrl('*/controlPanel/overviewTab');
        }
        $this->addTab(HelperControlPanel::TAB_OVERVIEW, $params);
        // ---------------------------------------

        $this->addTab(HelperControlPanel::TAB_INSPECTION, array(
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('Inspection'),
            'content' => $this->createBlock('ControlPanel\Tabs\Inspection')->toHtml(),
        ));

        $this->addTab(HelperControlPanel::TAB_VERSIONS_HISTORY, array(
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('Versions History'),
            'content' => $this->createBlock('ControlPanel\Tabs\VersionsHistory')->toHtml(),
        ));

        // ---------------------------------------
        $params = array('label' => $this->helperFactory->getObject('Module\Translation')->__('Database'));
        if ($activeTab == HelperControlPanel::TAB_DATABASE) {
            $params['content'] = $this->createBlock('ControlPanel\Tabs\Database')->toHtml();
        } else {
            $params['url'] = $this->getUrl('*/controlPanel/databaseTab');
        }
        $this->addTab(HelperControlPanel::TAB_DATABASE, $params);
        // ---------------------------------------

        $this->addTab(HelperControlPanel::TAB_TOOLS_GENERAL, array(
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('General Tools'),
            'content' => $this->createBlock('ControlPanel\Tabs\ToolsGeneral')->toHtml(),
        ));

        $this->addTab(HelperControlPanel::TAB_TOOLS_MODULE, array(
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('Module Tools'),
            'content' => $this->createBlock('ControlPanel\Tabs\ToolsModule')->toHtml(),
        ));

        $this->addTab(HelperControlPanel::TAB_DEBUG, array(
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('Debug'),
            'content' => $this->createBlock('ControlPanel\Tabs\Debug')->toHtml(),
        ));

        $this->setActiveTab($activeTab);

        return parent::_prepareLayout();
    }

    public function _toHtml()
    {
        return parent::_toHtml() . '<div id="control_panel_tab_container"></div>';
    }

    //########################################
}