<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

class Tabs extends AbstractTabs
{
    public const TAB_ID_GENERAL = 'general';
    public const TAB_ID_LISTING_OTHER = 'listingOther';
    public const TAB_ID_ORDERS = 'orders';
    public const TAB_ID_INVOICES_AND_SHIPMENTS = 'invoices_and_shipments';
    public const TAB_ID_FBA_INVENTORY = 'fba_inventory';
    public const TAB_MULTI_LOCATION_INVENTORY = 'multi_location_inventory';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    private \Ess\M2ePro\Helper\Magento $magentoHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->magentoHelper = $magentoHelper;
    }

    protected function _construct()
    {
        parent::_construct();

        $this->setId('amazonAccountEditTabs');
        $this->setDestElementId('edit_form');
    }

    protected function _prepareLayout()
    {
        $account = $this->getAccount();

        $this->addTab(self::TAB_ID_GENERAL, [
            'label' => __('General'),
            'title' => __('General'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\General::class)
                              ->toHtml(),
        ]);

        $this->addTab(self::TAB_ID_LISTING_OTHER, [
            'label' => __('Unmanaged Listings'),
            'title' => __('Unmanaged Listings'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\ListingOther::class)
                              ->toHtml(),
        ]);

        $this->addTab(self::TAB_ID_ORDERS, [
            'label' => __('Orders'),
            'title' => __('Orders'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order::class)
                              ->toHtml(),
        ]);

        if ($account !== null) {
            $this->addTab(self::TAB_ID_INVOICES_AND_SHIPMENTS, [
                'label' => __('Invoices & Shipments'),
                'title' => __('Invoices & Shipments'),
                'content' => $this->getLayout()
                                  ->createBlock(
                                      \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\InvoicesAndShipments::class
                                  )
                                  ->toHtml(),
            ]);
        }

        $this->addTab(self::TAB_ID_FBA_INVENTORY, [
            'label' => __('FBA Inventory'),
            'title' => __('FBA Inventory'),
            'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\FbaInventory::class)
                              ->toHtml(),
        ]);

        $this->addMultiLocationInventoryTab($account);

        $this->setActiveTab($this->getRequest()->getParam('tab', self::TAB_ID_GENERAL));

        return parent::_prepareLayout();
    }

    private function addMultiLocationInventoryTab(?\Ess\M2ePro\Model\Account $account): void
    {
        if (!$this->magentoHelper->isMSISupportingVersion()) {
            return;
        }

        if ($account === null) {
            return;
        }

        $marketplaceId = $account->getChildObject()->getMarketplaceId();
        if ($marketplaceId !== \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_US) {
            return;
        }

        $tabContent = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\MultiLocationInventory::class,
                '',
                ['account' => $account]
            )
            ->toHtml();

        $this->addTab(self::TAB_MULTI_LOCATION_INVENTORY, [
            'label' => __('Multi-Location Inventory'),
            'title' => __('Multi-Location Inventory'),
            'content' => $tabContent,
        ]);
    }

    private function getAccount(): ?\Ess\M2ePro\Model\Account
    {
        $account = $this->globalDataHelper->getValue('edit_account');
        if (
            empty($account)
            || empty($account->getId())
        ) {
            return null;
        }

        return $account;
    }
}
