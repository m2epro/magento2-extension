<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

class Tabs extends AbstractTabs
{
    public const TAB_ID_GENERAL = 'general';
    public const TAB_ID_LISTING_OTHER = 'listingOther';
    public const TAB_ID_STORE = 'store';
    public const TAB_ID_ORDER = 'order';
    public const TAB_ID_INVOICES_AND_SHIPMENTS = 'invoices_and_shipments';
    public const TAB_ID_FEEDBACK = 'feedback';
    public const TAB_ID_MY_STORES = 'my_stores';

    /** @var \Ess\M2ePro\Helper\Component\Ebay\PickupStore */
    private $componentEbayPickupStore;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\PickupStore $componentEbayPickupStore,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);

        $this->componentEbayPickupStore = $componentEbayPickupStore;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
    }

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
                'content' => $this->getLayout()
                                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\General::class)
                                  ->toHtml()
            ]
        );

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');
        $isShowTables = $this->getRequest()->getParam('is_show_tables', false);

        if ($isShowTables || $account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_LISTING_OTHER,
                [
                    'label'   => $this->__('Unmanaged Listings'),
                    'title'   => $this->__('Unmanaged Listings'),
                    'content' => $this->getLayout()
                                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\ListingOther::class)
                                  ->toHtml()
                ]
            );
        }

        if ($account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_STORE,
                [
                    'label'   => $this->__('eBay Store'),
                    'title'   => $this->__('eBay Store'),
                    'content' => $this->getLayout()
                                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Store::class)
                                      ->toHtml()
                ]
            );
        }

        if ($isShowTables || $account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_ORDER,
                [
                    'label'   => $this->__('Orders'),
                    'title'   => $this->__('Orders'),
                    'content' => $this->getLayout()
                                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Order::class)
                                      ->toHtml()
                ]
            );
        }

        if ($account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_INVOICES_AND_SHIPMENTS,
                [
                    'label'   => $this->__('Invoices & Shipments'),
                    'title'   => $this->__('Invoices & Shipments'),
                    'content' => $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\InvoicesAndShipments::class)
                          ->toHtml(),
                ]
            );
        }

        if ($account && $account->getId()) {
            $this->addTab(
                self::TAB_ID_FEEDBACK,
                [
                    'label'   => $this->__('Feedback'),
                    'title'   => $this->__('Feedback'),
                    'content' => $this->getLayout()
                                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback::class)
                                      ->toHtml()
                ]
            );
        }

        if ($account && $account->getId() && $this->componentEbayPickupStore->isFeatureEnabled()) {
            $this->addTab(
                self::TAB_ID_MY_STORES,
                [
                    'label'   => $this->__('In-Store Pickup'),
                    'title'   => $this->__('In-Store Pickup'),
                    'content' => $this->getLayout()
                                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\PickupStore::class)
                                  ->toHtml()
                ]
            );
        }

        $this->setActiveTab($this->getRequest()->getParam('tab', self::TAB_ID_GENERAL));

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay\Account'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay_Account_Feedback'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions(
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
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper
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
