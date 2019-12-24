<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Developers\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    const TAB_ID_INSTALLATION_DETAILS = 'installation_details';
    const TAB_ID_SYSTEM_REQUIREMENTS = 'system_requirements';
    const TAB_ID_CRON_JOB_INFO = 'cron_job_info';
    const TAB_ID_SYNCHRONIZATION_LOG = 'synchronization_log';
    const TAB_ID_DIRECT_DATABASE_CHANGES = 'direct_database_changes';
    const TAB_ID_PERFORMANCE_NOTES = 'performance_notes';

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('helpCenter');
        $this->setDestElementId('developers_tab_container');
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('developers.css');

        // ---------------------------------------
        $tab = [
            'label' => $this->__('About Module / Magento'),
            'title' => $this->__('About Module / Magento'),
            'content' => $this->createBlock('Developers_Tabs_AboutModule')->toHtml()
        ];

        $this->addTab(self::TAB_ID_INSTALLATION_DETAILS, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = [
            'label' => $this->__('System Requirements'),
            'title' => $this->__('System Requirements'),
            'content' => $this->createBlock('Developers_Tabs_SystemRequirements')->toHtml()
        ];

        $this->addTab(self::TAB_ID_SYSTEM_REQUIREMENTS, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = [
            'label' => $this->__('Cron Job Details / Status'),
            'title' => $this->__('Cron Job Details / Status'),
            'content' => $this->createBlock('Developers_Tabs_CronJobDetails', '', [
                'data' => ['is_support_mode' => true]
            ])->toHtml()
        ];

        $this->addTab(self::TAB_ID_CRON_JOB_INFO, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = [
            'label' => $this->__('Synchronization Log'),
            'title' => $this->__('Synchronization Log'),
            'content' => $this->createBlock('Developers_Tabs_SynchronizationLog')->toHtml()
        ];

        $this->addTab(self::TAB_ID_SYNCHRONIZATION_LOG, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = [
            'label' => $this->__('Direct Database Changes'),
            'title' => $this->__('Direct Database Changes'),
            'content' => $this->createBlock('Developers_Tabs_DirectDatabaseChanges')->toHtml()
        ];

        $this->addTab(self::TAB_ID_DIRECT_DATABASE_CHANGES, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = [
            'label' => $this->__('Performance Notes'),
            'title' => $this->__('Performance Notes'),
            'content' => $this->createBlock('Developers_Tabs_PerformanceNotes')->toHtml()
        ];

        $this->addTab(self::TAB_ID_PERFORMANCE_NOTES, $tab);

        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    public function getActiveTabById($id)
    {
        return isset($this->_tabs[$id]) ? $this->_tabs[$id] : null;
    }

    //########################################
}
