<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings;

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
            'content' => $this->createBlock('Ebay\Settings\Tabs\Main')->toHtml()
        ];

        $this->addTab(self::TAB_ID_MAIN, $tab);

        // ---------------------------------------

        // ---------------------------------------

        $tab = [
            'label' => __('Synchronization'),
            'title' => __('Synchronization'),
            'content' => $this->createBlock('Ebay\Settings\Tabs\Synchronization')->toHtml()
        ];

        $this->addTab(self::TAB_ID_SYNCHRONIZATION, $tab);

        // ---------------------------------------

        // ---------------------------------------

        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $epidsMarketplaceCollection */
        $epidsMarketplaceCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK, 'Marketplace'
        )->getCollection();
        $epidsMarketplaceCollection->addFieldToFilter('is_epid', 1);
        $epidsMarketplaceCollection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        $isMotorsEpidsMarketplaceEnabled = (bool)$epidsMarketplaceCollection->getSize();

        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $ktypeMarketplaceCollection */
        $ktypeMarketplaceCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK, 'Marketplace'
        )->getCollection();
        $ktypeMarketplaceCollection->addFieldToFilter('is_ktype', 1);
        $ktypeMarketplaceCollection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        $isMotorsKtypesMarketplaceEnabled = (bool)$ktypeMarketplaceCollection->getSize();

        if ($isMotorsEpidsMarketplaceEnabled || $isMotorsKtypesMarketplaceEnabled) {
            $tab = [
                'label' => __('Parts Compatibility'),
                'title' => __('Parts Compatibility'),
                'content' => $this->createBlock('Ebay\Settings\Tabs\Motors', '', [
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