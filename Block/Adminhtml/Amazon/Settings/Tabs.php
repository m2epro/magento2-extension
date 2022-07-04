<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Settings;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs
{
    const TAB_ID_MAIN = 'main';

    //########################################

    protected function _prepareLayout()
    {
        // ---------------------------------------

        $tab = [
            'label' => __('Main'),
            'title' => __('Main'),
            'content' => $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs\Main::class)
                                           ->toHtml()
        ];

        $this->addTab(self::TAB_ID_MAIN, $tab);

        // ---------------------------------------

        // ---------------------------------------

        $tab = [
            'label' => __('Synchronization'),
            'title' => __('Synchronization'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs\Synchronization::class)
                              ->toHtml()
        ];

        $this->addTab(self::TAB_ID_SYNCHRONIZATION, $tab);

        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add($this->getUrl('*/amazon/getGlobalMessages'), 'getGlobalMessages');
        return parent::_beforeToHtml();
    }

    //########################################
}
