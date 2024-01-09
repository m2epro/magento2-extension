<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Mapping;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    public const TAB_ID_SHIPPING_MAPPING = 'shippingmapping';
    public const TAB_ID_ATTRIBUTE_MAPPING = 'attributemapping';

    protected function _construct()
    {
        parent::_construct();
        $this->setId('mapping_tabs');
        $this->setDestElementId('tabs_container');
    }

    protected function _prepareLayout()
    {
        $tabShippingMapping = $this
            ->getLayout()
            ->createBlock(Tabs\ShippingMap\Main::class)
            ->toHtml();
        $this->addTab(self::TAB_ID_SHIPPING_MAPPING, [
            'label' => __('Shipping Mapping'),
            'title' => __('Shipping Mapping'),
            'content' => $tabShippingMapping,
        ]);

        $tabAttributeMappingContent = $this
            ->getLayout()
            ->createBlock(Tabs\AttributeMapping::class)
            ->toHtml();
        $this->addTab(self::TAB_ID_ATTRIBUTE_MAPPING, [
            'label' => __('Attribute Mapping'),
            'title' => __('Attribute Mapping'),
            'content' => $tabAttributeMappingContent,
        ]);
        $this->setActiveTab($this->getData('active_tab'));

        return parent::_prepareLayout();
    }

    public function getActiveTabById($id)
    {
        return isset($this->_tabs[$id]) ? $this->_tabs[$id] : null;
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->addTranslations([
            'Settings saved' => __('Settings saved'),
            'Error' => __('Error'),
        ]);
        $this->js->addRequireJs(
            [
                's' => 'M2ePro/Amazon/Mapping',
            ],
            <<<JS

        window.MappingObj = new Mapping();
JS
        );

        return parent::_beforeToHtml();
    }
}
