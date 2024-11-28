<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs
{
    public const TAB_ID_GENERAL = 'general';
    public const TAB_ID_MOTORS = 'motors';
    public const TAB_ID_MAPPING_ATTRIBUTES = 'mapping';

    private \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);

        $this->componentEbayMotors = $componentEbayMotors;
    }

    protected function _prepareLayout()
    {
        // ---------------------------------------

        $tab = [
            'label' => __('General'),
            'title' => __('General'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\General::class)
                              ->toHtml(),
        ];

        $this->addTab(self::TAB_ID_GENERAL, $tab);

        // ---------------------------------------

        $tab = [
            'label' => __('Synchronization'),
            'title' => __('Synchronization'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Synchronization::class)
                              ->toHtml(),
        ];

        $this->addTab(self::TAB_ID_SYNCHRONIZATION, $tab);

        // ---------------------------------------

        $isMotorsEpidsMarketplaceEnabled = $this->componentEbayMotors->isEPidMarketplacesEnabled();

        $isMotorsKtypesMarketplaceEnabled = $this->componentEbayMotors->isKTypeMarketplacesEnabled();

        if ($isMotorsEpidsMarketplaceEnabled || $isMotorsKtypesMarketplaceEnabled) {
            $tab = [
                'label' => __('Parts Compatibility'),
                'title' => __('Parts Compatibility'),
                'content' => $this->getLayout()->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Motors::class,
                    '',
                    [
                        'data' => [
                            'epids_enabled' => $isMotorsEpidsMarketplaceEnabled,
                            'ktypes_enabled' => $isMotorsKtypesMarketplaceEnabled,
                        ],
                    ]
                )->toHtml(),
            ];

            $this->addTab(self::TAB_ID_MOTORS, $tab);
        }

        $this->addTab(self::TAB_ID_MAPPING_ATTRIBUTES, [
            'label' => __('Attribute Mapping'),
            'title' => __('Attribute Mapping'),
            'content' => $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\AttributeMapping::class
            )->toHtml(),
        ]);

        // ---------------------------------------

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        $result = parent::_beforeToHtml();

        $this->jsUrl->add($this->getUrl('*/ebay/getGlobalMessages'), 'getGlobalMessages');

        $urlForSetGpsrToCategory = $this->getUrl('*/ebay_settings_attributeMapping/setGpsrToCategory');

        $this->jsPhp->addConstants(
            [
                '\Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_NONE' => \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_NONE,
                '\Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_CUSTOM' => \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_CUSTOM,
                '\Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE' => \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE
            ]
        );

        $this->js->addRequireJs(
            [
                's' => 'M2ePro/Ebay/Settings',
            ],
            <<<JS
        window.EbaySettingsObj = new EbaySettings("$urlForSetGpsrToCategory");
JS
        );

        return $result;
    }
}
