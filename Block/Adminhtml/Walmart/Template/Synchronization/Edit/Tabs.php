<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs
 */
class Tabs extends AbstractTabs
{
    //########################################

    protected function _construct()
    {
        parent::_construct();
        $this->setDestElementId('tabs_edit_form_data');
    }

    protected function _prepareLayout()
    {
        $this->addTab(
            'list_rules',
            [
                'label' => __('List Rules'),
                'title' => __('List Rules'),
                'content' => $this->getLayout()
                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs\ListRules::class)
                  ->toHtml(),
            ]
        );

        $this->addTab(
            'revise_rules',
            [
                'label' => __('Revise Rules'),
                'title' => __('Revise Rules'),
                'content' => $this->getLayout()
              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs\ReviseRules::class)
              ->toHtml(),
            ]
        );

        $this->addTab(
            'relist_rules',
            [
                'label' => __('Relist Rules'),
                'title' => __('Relist Rules'),
                'content' => $this->getLayout()
              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs\RelistRules::class)
              ->toHtml(),
            ]
        );

        $this->addTab(
            'stop_rules',
            [
                'label' => __('Stop Rules'),
                'title' => __('Stop Rules'),
                'content' => $this->getLayout()
                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Tabs\StopRules::class)
                  ->toHtml(),
            ]
        );

        return parent::_prepareLayout();
    }

    //########################################
}
