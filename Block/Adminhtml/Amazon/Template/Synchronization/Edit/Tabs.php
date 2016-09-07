<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

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
                'content' => $this->createBlock(
                    'Amazon\Template\Synchronization\Edit\Tabs\ListRules'
                )->toHtml(),
            ]
        );

        $this->addTab(
            'revise_rules',
            [
                'label' => __('Revise Rules'),
                'title' => __('Revise Rules'),
                'content' => $this->createBlock(
                    'Amazon\Template\Synchronization\Edit\Tabs\ReviseRules'
                )->toHtml(),
            ]
        );

        $this->addTab(
            'relist_rules',
            [
                'label' => __('Relist Rules'),
                'title' => __('Relist Rules'),
                'content' => $this->createBlock(
                    'Amazon\Template\Synchronization\Edit\Tabs\RelistRules'
                )->toHtml(),
            ]
        );

        $this->addTab(
            'stop_rules',
            [
                'label' => __('Stop Rules'),
                'title' => __('Stop Rules'),
                'content' => $this->createBlock(
                    'Amazon\Template\Synchronization\Edit\Tabs\StopRules'
                )->toHtml(),
            ]
        );

        return parent::_prepareLayout();
    }

    //########################################
}