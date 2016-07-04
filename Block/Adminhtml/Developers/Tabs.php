<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    const TAB_ID_INSTALLATION_DETAILS = 'installation_details';
    const TAB_ID_SYSTEM_REQUIREMENTS = 'system_requirements';
    const TAB_ID_CRON_JOB_INFO = 'cron_job_info';
    const TAB_ID_SYNCHRONIZATION_LOG = 'synchronization_log';
    const TAB_ID_MAGMI_PLUGIN = 'magmi_plugin';
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
        $tab = array(
            'label' => $this->__('About Module / Magento'),
            'title' => $this->__('About Module / Magento'),
            'content' => $this->createBlock('Developers\Tabs\AboutModule')->toHtml()
        );

        $this->addTab(self::TAB_ID_INSTALLATION_DETAILS, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = array(
            'label' => $this->__('System Requirements'),
            'title' => $this->__('System Requirements'),
            'content' => $this->createBlock('Developers\Tabs\SystemRequirements')->toHtml()
        );

        $this->addTab(self::TAB_ID_SYSTEM_REQUIREMENTS, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = array(
            'label' => $this->__('Cron Job Details / Status'),
            'title' => $this->__('Cron Job Details / Status'),
            'content' => $this->createBlock('Developers\Tabs\CronJobDetails', '', [
                'data' => ['is_support_mode' => true]
            ])->toHtml()
        );

        $this->addTab(self::TAB_ID_CRON_JOB_INFO, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = array(
            'label' => $this->__('Synchronization Log'),
            'title' => $this->__('Synchronization Log'),
            'content' => $this->createBlock('Developers\Tabs\SynchronizationLog')->toHtml()
        );

        $this->addTab(self::TAB_ID_SYNCHRONIZATION_LOG, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = array(
            'label' => $this->__('Magmi Import Tool Plugin'),
            'title' => $this->__('Magmi Plugin'),
            'content' => $this->createBlock('Developers\Tabs\MagmiPlugin')->toHtml()
        );

        $this->addTab(self::TAB_ID_MAGMI_PLUGIN, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = array(
            'label' => $this->__('Direct Database Changes'),
            'title' => $this->__('Direct Database Changes'),
            'content' => $this->createBlock('Developers\Tabs\DirectDatabaseChanges')->toHtml()
        );

        $this->addTab(self::TAB_ID_DIRECT_DATABASE_CHANGES, $tab);

        // ---------------------------------------

        // ---------------------------------------
        $tab = array(
            'label' => $this->__('Performance Notes'),
            'title' => $this->__('Performance Notes'),
            'content' => $this->createBlock('Developers\Tabs\PerformanceNotes')->toHtml()
        );

        $this->addTab(self::TAB_ID_PERFORMANCE_NOTES, $tab);

        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    public function getActiveTabById($id)
    {
        return isset($this->_tabs[$id]) ? $this->_tabs[$id] : NULL;
    }

    //########################################
}