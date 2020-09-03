<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs;
use \Ess\M2ePro\Helper\View\ControlPanel as HelperControlPanel;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs
 */
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
        $allowedTabs = [
            HelperControlPanel::TAB_OVERVIEW,
            HelperControlPanel::TAB_INSPECTION,
            HelperControlPanel::TAB_DATABASE,
            HelperControlPanel::TAB_TOOLS_MODULE,
            HelperControlPanel::TAB_CRON,
            HelperControlPanel::TAB_DEBUG,
        ];

        if (!in_array($activeTab, $allowedTabs)) {
            $activeTab = HelperControlPanel::TAB_OVERVIEW;
        }

        // ---------------------------------------
        $this->addTab(HelperControlPanel::TAB_OVERVIEW, [
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('Overview'),
            'content' => $this->createBlock('ControlPanel_Tabs_Overview')->toHtml()
        ]);
        // ---------------------------------------
        $params = ['label' => $this->helperFactory->getObject('Module\Translation')->__('Inspection')];
        if ($activeTab == HelperControlPanel::TAB_INSPECTION) {
            $params['content'] = $this->createBlock('ControlPanel_Tabs_Inspection')->toHtml();
        } else {
            $params['class'] = 'ajax';
            $params['url'] = $this->getUrl('*/controlPanel/InspectionTab');
        }
        $this->addTab(HelperControlPanel::TAB_INSPECTION, $params);

        // ---------------------------------------
        $params = ['label' => $this->helperFactory->getObject('Module\Translation')->__('Database')];
        if ($activeTab == HelperControlPanel::TAB_DATABASE) {
            $params['content'] = $this->createBlock('ControlPanel_Tabs_Database')->toHtml();
        } else {
            $params['class'] = 'ajax';
            $params['url'] = $this->getUrl('*/controlPanel/databaseTab');
        }
        $this->addTab(HelperControlPanel::TAB_DATABASE, $params);
        // ---------------------------------------

        $this->addTab(HelperControlPanel::TAB_TOOLS_MODULE, [
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('Module Tools'),
            'content' => $this->createBlock('ControlPanel_Tabs_ToolsModule')->toHtml(),
        ]);

        $this->addTab(HelperControlPanel::TAB_CRON, [
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('Cron'),
            'content' => $this->createBlock('ControlPanel_Tabs_Cron')->toHtml(),
        ]);

        $this->addTab(HelperControlPanel::TAB_DEBUG, [
            'label'   => $this->helperFactory->getObject('Module\Translation')->__('Debug'),
            'content' => $this->createBlock('ControlPanel_Tabs_Debug')->toHtml(),
        ]);

        $this->setActiveTab($activeTab);

        return parent::_prepareLayout();
    }

    public function _toHtml()
    {
        $this->js->add(
            <<<JS
function SetupManagementActionHandler() {

    this.askAdditionalParametersForAction = function(promptString, url, placeHolder)
    {
        var result = prompt(promptString);

        if (result == null) {
            return false;
        }
        
        url = url.replace(encodeURIComponent('#') + placeHolder + encodeURIComponent('#'), result);
        document.location = url;
    }
}

window.setupManagementActionHandler = new SetupManagementActionHandler();
JS
        );
        return parent::_toHtml() . '<div id="control_panel_tab_container"></div>';
    }

    //########################################
}
