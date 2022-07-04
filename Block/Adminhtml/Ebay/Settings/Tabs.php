<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs
{
    const TAB_ID_MAIN = 'main';
    const TAB_ID_MOTORS = 'motors';

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

    protected function _prepareLayout()
    {
        // ---------------------------------------

        $tab = [
            'label' => __('Main'),
            'title' => __('Main'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Main::class)
                              ->toHtml()
        ];

        $this->addTab(self::TAB_ID_MAIN, $tab);

        // ---------------------------------------

        $tab = [
            'label' => __('Synchronization'),
            'title' => __('Synchronization'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Synchronization::class)
                              ->toHtml()
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
                        'epids_enabled'  => $isMotorsEpidsMarketplaceEnabled,
                        'ktypes_enabled' => $isMotorsKtypesMarketplaceEnabled
                    ]
                    ]
                )->toHtml()
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
