<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs
{
    public const TAB_ID_GENERAL = 'general';
    public const TAB_ID_MOTORS = 'motors';
    public const TAB_ID_MAPPING = 'mapping';

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

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

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Settings\Tabs
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

        $this->addTab(self::TAB_ID_MAPPING, [
            'label' => __('Mapping'),
            'title' => __('Mapping'),
            'content' => $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Mapping::class
            )->toHtml(),
        ]);

        // ---------------------------------------

        return parent::_prepareLayout();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Settings\Tabs|\Magento\Backend\Block\Widget\Tabs
     */
    protected function _beforeToHtml()
    {
        $this->jsUrl->add($this->getUrl('*/ebay/getGlobalMessages'), 'getGlobalMessages');

        return parent::_beforeToHtml();
    }
}
