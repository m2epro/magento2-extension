<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractVerticalTabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs
 */
class Tabs extends AbstractVerticalTabs
{
    protected $_template = 'Magento_Backend::widget/tabs.phtml';

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
                    'Ebay_Template_Synchronization_Edit_Form_Tabs_ListRules'
                )->toHtml(),
            ]
        );

        $this->addTab(
            'revise_rules',
            [
                'label' => __('Revise Rules'),
                'title' => __('Revise Rules'),
                'content' => $this->createBlock(
                    'Ebay_Template_Synchronization_Edit_Form_Tabs_ReviseRules'
                )->toHtml(),
            ]
        );

        $this->addTab(
            'relist_rules',
            [
                'label' => __('Relist Rules'),
                'title' => __('Relist Rules'),
                'content' => $this->createBlock(
                    'Ebay_Template_Synchronization_Edit_Form_Tabs_RelistRules'
                )->toHtml(),
            ]
        );

        $this->addTab(
            'stop_rules',
            [
                'label' => __('Stop Rules'),
                'title' => __('Stop Rules'),
                'content' => $this->createBlock(
                    'Ebay_Template_Synchronization_Edit_Form_Tabs_StopRules'
                )->toHtml(),
            ]
        );

        return parent::_prepareLayout();
    }
}
