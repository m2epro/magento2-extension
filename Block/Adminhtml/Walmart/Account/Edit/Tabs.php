<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs
 */
class Tabs extends AbstractTabs
{
    const TAB_ID_GENERAL                = 'general';
    const TAB_ID_LISTING_OTHER          = 'listingOther';
    const TAB_ID_ORDERS                 = 'orders';
    const TAB_ID_INVOICES_AND_SHIPMENTS = 'invoices_and_shipments';

    protected function _construct()
    {
        parent::_construct();

        $this->setId('walmartAccountEditTabs');
        $this->setDestElementId('edit_form');
    }

    protected function _prepareLayout()
    {
        $this->addTab(
            self::TAB_ID_GENERAL,
            [
                'label'   => $this->__('General'),
                'title'   => $this->__('General'),
                'content' => $this->getLayout()
                                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\General::class)
                                  ->toHtml(),
            ]
        );

        $this->addTab(
            self::TAB_ID_LISTING_OTHER,
            [
                'label'   => $this->__('Unmanaged Listings'),
                'title'   => $this->__('Unmanaged Listings'),
                'content' => $this->getLayout()
                              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\ListingOther::class)
                              ->toHtml(),
            ]
        );

        $this->addTab(
            self::TAB_ID_ORDERS,
            [
                'label'   => $this->__('Orders'),
                'title'   => $this->__('Orders'),
                'content' => $this->getLayout()
                                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\Order::class)
                                  ->toHtml(),
            ]
        );

        $this->addTab(
            self::TAB_ID_INVOICES_AND_SHIPMENTS,
            [
                'label'   => $this->__('Invoices & Shipments'),
                'title'   => $this->__('Invoices & Shipments'),
                'content' => $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\InvoicesAndShipments::class)
                      ->toHtml(),
            ]
        );

        $this->setActiveTab($this->getRequest()->getParam('tab', self::TAB_ID_GENERAL));

        $this->js->addOnReadyJs(
            <<<JS
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
}
