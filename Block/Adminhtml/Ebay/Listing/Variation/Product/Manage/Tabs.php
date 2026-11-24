<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\OptionsConfigurationLoader $optionsConfigurationLoader;
    private \Ess\M2ePro\Model\Listing\Product $listingProduct;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\OptionsConfigurationLoader $optionsConfigurationLoader,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->listingProduct = $listingProduct;
        $this->optionsConfigurationLoader = $optionsConfigurationLoader;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayVariationProductManageTabs');
        $this->setDestElementId('variation_product_manage_tabs_container');
    }

    protected function _beforeToHtml()
    {
        $this->addVariationsTab();
        $this->addSettingsTab();

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $css = <<<CSS
li:has(.tab-item-link.tab-disabled) {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
CSS;

        $this->css->add($css);

        return '<div id="variation_manage_tabs_container">' . parent::_toHtml() .
            '<div id="variation_product_manage_tabs_container"></div></div>';
    }

    // ----------------------------------------

    private function addVariationsTab(): void
    {
        $tabContent = $this
            ->getLayout()
            ->createBlock(Tabs\Variations::class, '', [
                'listingProduct' => $this->listingProduct,
            ])
            ->toHtml();

        $this->addTab('variations', [
            'label' => __('Child Products'),
            'title' => __('Child Products'),
            'content' => $tabContent,
        ]);
    }

    private function addSettingsTab(): void
    {
        $variationOptions = $this->optionsConfigurationLoader
            ->execute($this->listingProduct);

        $tabContent = '';
        $tabClass = 'tab-disabled';
        if (!empty($variationOptions)) {
            $tabContent = $this
                ->getLayout()
                ->createBlock(
                    Tabs\OptionMapping::class,
                    '',
                    [
                        'listingProduct' => $this->listingProduct,
                        'variationOptions' => $variationOptions,
                    ]
                )
                ->toHtml();
            $tabClass = '';
        }

        $this->addTab('option_mapping', [
            'label' => __('Option Mapping'),
            'title' => __('Option Mapping'),
            'content' => $tabContent,
            'class' => $tabClass,
        ]);
    }
}
