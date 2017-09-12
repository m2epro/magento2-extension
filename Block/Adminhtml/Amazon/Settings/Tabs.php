<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Settings;

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
            'content' => $this->createBlock('Amazon\Settings\Tabs\Main')->toHtml()
        ];

        $this->addTab(self::TAB_ID_MAIN, $tab);

        // ---------------------------------------

        // ---------------------------------------

        $tab = array(
            'label' => __('Synchronization'),
            'title' => __('Synchronization'),
            'content' => $this->createBlock('Amazon\Settings\Tabs\Synchronization')->toHtml()
        );

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