<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs
 */
class Tabs extends AbstractTabs
{
    const TAB_ID_GENERAL                = 'general';
    const TAB_ID_LISTING_OTHER          = 'listingOther';
    const TAB_ID_STORE                  = 'store';
    const TAB_ID_ORDER                  = 'order';
    const TAB_ID_INVOICES_AND_SHIPMENTS = 'invoices_and_shipments';
    const TAB_ID_FEEDBACK               = 'feedback';
    const TAB_ID_MY_STORES              = 'my_stores';

    protected function _construct()
    {
        parent::_construct();

        $this->setId('ebayAccountEditTabs');
        $this->setDestElementId('edit_form');
    }

    protected function _beforeToHtml()
    {
        $this->addTab(
            self::TAB_ID_GENERAL,
            [
                'label'   => __('General'),
                'title'   => __('General'),
                'content' => $this->createBlock('Ebay_Account_Edit_Tabs_General')->toHtml()
            ]
        );

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $isShowTables = $this->getRequest()->getParam('is_show_tables', false);

        if ($isShowTables || $account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_LISTING_OTHER,
                [
                    'label'   => $this->__('Unmanaged Listings'),
                    'title'   => $this->__('Unmanaged Listings'),
                    'content' => $this->createBlock('Ebay_Account_Edit_Tabs_ListingOther')->toHtml()
                ]
            );
        }

        if ($account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_STORE,
                [
                    'label'   => $this->__('eBay Store'),
                    'title'   => $this->__('eBay Store'),
                    'content' => $this->createBlock('Ebay_Account_Edit_Tabs_Store')->toHtml()
                ]
            );
        }

        if ($isShowTables || $account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_ORDER,
                [
                    'label'   => $this->__('Orders'),
                    'title'   => $this->__('Orders'),
                    'content' => $this->createBlock('Ebay_Account_Edit_Tabs_Order')->toHtml()
                ]
            );
        }

        if ($account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_INVOICES_AND_SHIPMENTS,
                [
                    'label'   => $this->__('Invoices & Shipments'),
                    'title'   => $this->__('Invoices & Shipments'),
                    'content' => $this->createBlock('Ebay_Account_Edit_Tabs_InvoicesAndShipments')->toHtml(),
                ]
            );
        }

        if ($account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_FEEDBACK,
                [
                    'label'   => $this->__('Feedback'),
                    'title'   => $this->__('Feedback'),
                    'content' => $this->createBlock('Ebay_Account_Edit_Tabs_Feedback')->toHtml()
                ]
            );
        }

        if ($account && $account->getId() && $this->getHelper('Component_Ebay_PickupStore')->isFeatureEnabled()) {
            $this->addTab(
                self::TAB_ID_MY_STORES,
                [
                    'label'   => $this->__('In-Store Pickup'),
                    'title'   => $this->__('In-Store Pickup'),
                    'content' => $this->createBlock('Ebay_Account_Edit_Tabs_PickupStore')->toHtml()
                ]
            );
        }

        $this->setActiveTab($this->getRequest()->getParam('tab', self::TAB_ID_GENERAL));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Account'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay_Account_Feedback'));
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions(
                'Ebay_Account_Feedback_Template',
                [
                    'account_id' => $this->getRequest()->getParam('id')
                ]
            )
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_account/beforeGetToken', ['_current' => true]),
            'ebay_account/beforeGetToken'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_account/beforeGetSellApiToken', ['_current' => true]),
            'ebay_account/beforeGetSellApiToken'
        );
        $this->jsUrl->addUrls(
            [
                'formSubmit'   => $this->getUrl(
                    '*/ebay_account/save',
                    ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
                ),
                'deleteAction' => $this->getUrl('*/ebay_account/delete', ['id' => $this->getRequest()->getParam('id')])
            ]
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')
                ->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay::class)
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')
                ->getClassConstants(\Ess\M2ePro\Model\Ebay\Account::class)
        );

        $this->jsTranslator->addTranslations(
            [
                'The specified Title is already used for other Account. Account Title must be unique.' => $this->__(
                    'The specified Title is already used for other Account. Account Title must be unique.'
                ),
                'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                . 'This will cause inappropriate work of all Accounts\' copies.'                       => $this->__(
                    'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                    . 'This will cause inappropriate work of all Accounts\' copies.'
                ),
                'No Customer entry is found for specified ID.'                                         => $this->__(
                    'No Customer entry is found for specified ID.'
                ),
                'If Yes is chosen, you must select at least one Attribute for Product Linking.'        => $this->__(
                    'If Yes is chosen, you must select at least one Attribute for Product Linking.'
                ),
                'You should create at least one Response Template.'                                    => $this->__(
                    'You should create at least one Response Template.'
                ),
                'You must get token.'                                                                  => $this->__(
                    'You must get token.'
                )
            ]
        );

        return parent::_beforeToHtml();
    }
}
