<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;
use Ess\M2ePro\Helper\Component\Amazon;

class Tabs extends AbstractTabs
{
    const TAB_ID_GENERAL                = 'general';
    const TAB_ID_LISTING_OTHER          = 'listingOther';
    const TAB_ID_ORDERS                 = 'orders';
    const TAB_ID_INVOICES_AND_SHIPMENTS = 'invoices_and_shipments';
    const TAB_ID_REPRICING              = 'repricing';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Repricing */
    private $amazonRepricingHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Repricing $amazonRepricingHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        $this->amazonRepricingHelper = $amazonRepricingHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->setId('amazonAccountEditTabs');
        $this->setDestElementId('edit_form');
    }

    protected function _prepareLayout()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');

        $this->addTab(self::TAB_ID_GENERAL, [
            'label'   => $this->__('General'),
            'title'   => $this->__('General'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\General::class)
                              ->toHtml(),
        ]);

        $this->addTab(self::TAB_ID_LISTING_OTHER, [
            'label'   => $this->__('Unmanaged Listings'),
            'title'   => $this->__('Unmanaged Listings'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\ListingOther::class)
                              ->toHtml(),
        ]);

        $this->addTab(self::TAB_ID_ORDERS, [
            'label'   => $this->__('Orders'),
            'title'   => $this->__('Orders'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order::class)
                              ->toHtml(),
        ]);

        if ($account !== null && $account->getId()) {
            $this->addTab(self::TAB_ID_INVOICES_AND_SHIPMENTS, [
                'label'   => $this->__('Invoices & Shipments'),
                'title'   => $this->__('Invoices & Shipments'),
                'content' => $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\InvoicesAndShipments::class)
                      ->toHtml(),
            ]);
        }

        if ($this->isRepricingSupported($account)) {
            $this->addTab(self::TAB_ID_REPRICING, [
                'label'   => $this->__('Repricing Tool'),
                'title'   => $this->__('Repricing Tool'),
                'content' => $this->getLayout()
                                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Repricing::class)
                                  ->toHtml(),
            ]);
        }

        $this->setActiveTab($this->getRequest()->getParam('tab', self::TAB_ID_GENERAL));

        $this->js->addOnReadyJs(<<<JS

    var urlHash = location.hash.substr(1);
    if (urlHash != '') {
        setTimeout(function() {
            jQuery('#{$this->getId()}').tabs(
                'option', 'active', jQuery('li[aria-labelledby="{$this->getId()}_' + urlHash + '"]').index()
                );
            location.hash = '';
        }, 100);
    }
JS
        );

        return parent::_prepareLayout();
    }

    /**
     * @param \Ess\M2ePro\Model\Account|null $account
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isRepricingSupported($account)
    {
        $supportedMarketplaces = [
            Amazon::MARKETPLACE_US,
            Amazon::MARKETPLACE_CA,
            Amazon::MARKETPLACE_MX,
            Amazon::MARKETPLACE_UK,
            Amazon::MARKETPLACE_DE,
            Amazon::MARKETPLACE_IT,
            Amazon::MARKETPLACE_FR,
            Amazon::MARKETPLACE_ES,
            Amazon::MARKETPLACE_AU,
        ];

        return $account !== null
            && $account->getId()
            && $this->amazonRepricingHelper->isEnabled()
            && in_array($account->getChildObject()->getMarketplaceId(), $supportedMarketplaces);
    }
}
