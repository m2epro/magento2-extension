<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;
use Ess\M2ePro\Helper\Component\Amazon;

class Tabs extends AbstractTabs
{
    public const TAB_ID_GENERAL = 'general';
    public const TAB_ID_LISTING_OTHER = 'listingOther';
    public const TAB_ID_ORDERS = 'orders';
    public const TAB_ID_INVOICES_AND_SHIPMENTS = 'invoices_and_shipments';
    public const TAB_ID_FBA_INVENTORY = 'fba_inventory';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
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

        if ($account !== null && $account->getId()) {
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

        $this->setActiveTab($this->getRequest()->getParam('tab', self::TAB_ID_GENERAL));

        return parent::_prepareLayout();
    }
}
