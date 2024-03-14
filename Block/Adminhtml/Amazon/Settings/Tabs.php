<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Settings;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs
{
    public const TAB_ID_GENERAL = 'general';
    public const TAB_ID_ATTRIBUTE_MAPPING = 'attributemapping';

    protected function _prepareLayout()
    {
        $tabMainContent = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs\General::class)
            ->toHtml();
        $this->addTab(self::TAB_ID_GENERAL, [
            'label' => __('General'),
            'title' => __('General'),
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

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        $this->jsUrl->add($this->getUrl('*/amazon/getGlobalMessages'), 'getGlobalMessages');

        return parent::_beforeToHtml();
    }
}
