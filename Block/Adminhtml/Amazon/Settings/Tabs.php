<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Settings;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs
{
    public const TAB_ID_MAIN = 'main';
    public const TAB_ID_ATTRIBUTE_MAPPING = 'attributemapping';

    protected function _prepareLayout()
    {
        $tabMainContent = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs\Main::class)
            ->toHtml();
        $this->addTab(self::TAB_ID_MAIN, [
            'label' => __('Main'),
            'title' => __('Main'),
            'content' => $tabMainContent,
        ]);

        $tabSynchronizationContent = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs\Synchronization::class)
            ->toHtml();
        $this->addTab(self::TAB_ID_SYNCHRONIZATION, [
            'label' => __('Synchronization'),
            'title' => __('Synchronization'),
            'content' => $tabSynchronizationContent,
        ]);

        $tabAttributeMappingContent = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs\AttributeMapping::class)
            ->toHtml();
        $this->addTab(self::TAB_ID_ATTRIBUTE_MAPPING, [
            'label' => __('Attribute Mapping'),
            'title' => __('Attribute Mapping'),
            'content' => $tabAttributeMappingContent,
        ]);

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        $this->jsUrl->add($this->getUrl('*/amazon/getGlobalMessages'), 'getGlobalMessages');

        return parent::_beforeToHtml();
    }
}
