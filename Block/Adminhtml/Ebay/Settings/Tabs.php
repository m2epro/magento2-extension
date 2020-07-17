<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs
{
    const TAB_ID_MAIN = 'main';
    const TAB_ID_MOTORS = 'motors';

    //########################################

    protected function _prepareLayout()
    {
        // ---------------------------------------

        $tab = [
            'label' => __('Main'),
            'title' => __('Main'),
            'content' => $this->createBlock('Ebay_Settings_Tabs_Main')->toHtml()
        ];

        $this->addTab(self::TAB_ID_MAIN, $tab);

        // ---------------------------------------

        // ---------------------------------------

        $tab = [
            'label' => __('Synchronization'),
            'title' => __('Synchronization'),
            'content' => $this->createBlock('Ebay_Settings_Tabs_Synchronization')->toHtml()
        ];

        $this->addTab(self::TAB_ID_SYNCHRONIZATION, $tab);

        // ---------------------------------------

        // ---------------------------------------

        $isMotorsEpidsMarketplaceEnabled = $this->getHelper('Component_Ebay_Motors')
            ->isEPidMarketplacesEnabled();

        $isMotorsKtypesMarketplaceEnabled = $this->getHelper('Component_Ebay_Motors')
            ->isKTypeMarketplacesEnabled();

        if ($isMotorsEpidsMarketplaceEnabled || $isMotorsKtypesMarketplaceEnabled) {
            $tab = [
                'label' => __('Parts Compatibility'),
                'title' => __('Parts Compatibility'),
                'content' => $this->createBlock('Ebay_Settings_Tabs_Motors', '', [
                    'data' => [
                        'epids_enabled'  => $isMotorsEpidsMarketplaceEnabled,
                        'ktypes_enabled' => $isMotorsKtypesMarketplaceEnabled
                    ]
                ])->toHtml()
            ];

            $this->addTab(self::TAB_ID_MOTORS, $tab);
        }
        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add($this->getUrl('*/ebay/getGlobalMessages'), 'getGlobalMessages');
        return parent::_beforeToHtml();
    }

    //########################################
}
