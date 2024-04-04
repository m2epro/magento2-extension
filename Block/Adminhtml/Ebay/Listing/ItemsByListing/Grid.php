<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByListing;

use Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Grid
{
    public const MASS_ACTION_ID_EDIT_PARTS_COMPATIBILITY = 'editPartsCompatibilityMode';

    /** @var \Ess\M2ePro\Model\ResourceModel\Account */
    private $accountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product*/
    private $ebayListingProductResource;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;
    /** @var \Ess\M2ePro\Helper\Url */
    private $urlHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory */
    private $listingCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Url $urlHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        array $data = []
    ) {
        parent::__construct($viewHelper, $context, $backendHelper, $dataHelper, $data);

        $this->accountResource = $accountResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->listingProductResource = $listingProductResource;
        $this->ebayListingProductResource = $ebayListingProductResource;
        $this->ebayFactory = $ebayFactory;
        $this->urlHelper = $urlHelper;
        $this->listingCollectionFactory = $listingCollectionFactory;
    }

    /**
     * @ingeritdoc
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('ebayListingItemsByListingGrid');
    }

    /**
     * @ingeritdoc
     */
    public function getRowUrl($item)
    {
        return $this->getUrl(
            '*/ebay_listing/view',
            [
                'id' => $item->getId(),
                'back' => $this->getBackUrl()
            ]
        );
    }

    /**
     * @return string
     */
    private function getBackUrl(): string
    {
        return $this->urlHelper->makeBackUrlParam('*/ebay_listing/index');
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByListing\Grid
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareCollection()
    {
        $collection = $this->listingCollectionFactory->createWithEbayChildMode();
        $collection->getSelect()->join(
            ['a' => $this->accountResource->getMainTable()],
            'a.id = main_table.account_id',
            ['account_title' => 'title']
        );
        $collection->getSelect()->join(
            ['m' => $this->marketplaceResource->getMainTable()],
            'm.id = main_table.marketplace_id',
            ['marketplace_title' => 'title']
        );

        $select = $collection->getConnection()->select();
        $select->from(['elp' => $this->ebayListingProductResource->getMainTable()], [
            'items_sold_count' => new \Zend_Db_Expr('IFNULL(SUM(elp.online_qty_sold), 0)'),
        ]);
        $select->joinLeft(
            ['lp' => $this->listingProductResource->getMainTable()],
            'lp.id = elp.listing_product_id',
            [
                'listing_id' => 'listing_id',
                'products_total_count' => new \Zend_Db_Expr('COUNT(lp.id)'),
                'products_active_count' => new \Zend_Db_Expr('COUNT(IF(lp.status = 2, lp.id, NULL))'),
                'products_inactive_count' => new \Zend_Db_Expr('COUNT(IF(lp.status != 2, lp.id, NULL))'),
            ]
        );
        $select->group('lp.listing_id');

        $collection->getSelect()->joinLeft(
            ['t' => $select],
            'main_table.id=t.listing_id',
            [
                'products_total_count' => 'products_total_count',
                'products_active_count' => 'products_active_count',
                'products_inactive_count' => 'products_inactive_count',
                'items_sold_count' => 'items_sold_count',
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @ingeritdoc
     */
    protected function _prepareLayout()
    {
        $this->css->addFile('ebay/listing/grid.css');

        return parent::_prepareLayout();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function setColumns()
    {
        $this->addColumn(
            'items_sold_count',
            [
                'header' => __('Sold QTY'),
                'align' => 'right',
                'type' => 'number',
                'index' => 'items_sold_count',
                'filter_index' => 't.items_sold_count',
                'frame_callback' => [$this, 'callbackColumnProductsCount'],
            ]
        );

        return $this;
    }

    /**
     * @return array[]
     */
    protected function getColumnActionsItems()
    {
        $backUrl = $this->getBackUrl();

        return [
            'manageProducts' => [
                'caption' => __('Manage'),
                'group' => 'products_actions',
                'field' => 'id',
                'url' => [
                    'base' => '*/ebay_listing/view',
                    'params' => [
                        'id' => $this->getId(),
                        'back' => $backUrl,
                    ],
                ],
            ],

            'addProductsSourceProducts' => [
                'caption' => __('Add From Products List'),
                'group' => 'products_actions',
                'field' => 'id',
                'onclick_action' => 'EbayListingGridObj.addProductsSourceProductsAction',
            ],

            'addProductsSourceCategories' => [
                'caption' => __('Add From Categories'),
                'group' => 'products_actions',
                'field' => 'id',
                'onclick_action' => 'EbayListingGridObj.addProductsSourceCategoriesAction',
            ],

            'autoActions' => [
                'caption' => __('Auto Add/Remove Rules'),
                'group' => 'products_actions',
                'field' => 'id',
                'url' => [
                    'base' => '*/ebay_listing/view',
                    'params' => [
                        'id' => $this->getId(),
                        'auto_actions' => 1,
                        'back' => $backUrl,
                    ],
                ],
            ],

            'editTitle' => [
                'caption' => __('Title'),
                'group' => 'edit_actions',
                'field' => 'id',
                'onclick_action' => 'EditListingTitleObj.openPopup',
            ],

            'editStoreView' => [
                'caption' => __('Store View'),
                'group' => 'edit_actions',
                'field' => 'id',
                'onclick_action' => 'EditListingStoreViewObj.openPopup',
            ],

            'editConfiguration' => [
                'caption' => __('Configuration'),
                'group' => 'edit_actions',
                'field' => 'id',
                'url' => [
                    'base' => '*/ebay_listing/edit',
                    'params' => ['back' => $backUrl],
                ],
            ],

            self::MASS_ACTION_ID_EDIT_PARTS_COMPATIBILITY => [
                'caption' => __('Parts Compatibility Mode'),
                'group' => 'edit_actions',
                'field' => 'id',
                'onclick_action' => 'EditCompatibilityModeObj.openPopup',
                'action_id' => self::MASS_ACTION_ID_EDIT_PARTS_COMPATIBILITY,
            ],

            'viewLogs' => [
                'caption' => __('Logs & Events'),
                'group' => 'other',
                'field' => \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD,
                'url' => [
                    'base' => '*/ebay_log_listing_product/index',
                ],
            ],

            'clearLogs' => [
                'caption' => __('Clear Log'),
                'confirm' => __('Are you sure?'),
                'group' => 'other',
                'field' => 'id',
                'url' => [
                    'base' => '*/listing/clearLog',
                    'params' => [
                        'back' => $backUrl,
                    ],
                ],
            ],

            'delete' => [
                'caption' => __('Delete Listing'),
                'confirm' => __('Are you sure?'),
                'group' => 'other',
                'field' => 'id',
                'url' => [
                    'base' => '*/ebay_listing/delete',
                    'params' => ['id' => $this->getId()],
                ],
            ],
        ];
    }

    /**
     * editPartsCompatibilityMode has to be not accessible for not Multi Motors marketplaces
     * @return $this
     */
    protected function _prepareColumns()
    {
        $result = parent::_prepareColumns();

        $this->getColumn('actions')->setData(
            'renderer',
            \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Column\Renderer\Action::class
        );

        return $result;
    }

    /**
     * @param string $value
     * @param \Ess\M2ePro\Model\Listing $row
     * @param Rewrite $column
     * @param bool $isExport
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $title = $this->dataHelper->escapeHtml($value);
        $compatibilityMode = $row->getChildObject()->getData('parts_compatibility_mode');

        $value = <<<HTML
<span id="listing_title_{$row->getId()}">
    {$title}
</span>
<span id="listing_compatibility_mode_{$row->getId()}" style="display: none;">
    {$compatibilityMode}
</span>
HTML;

        $accountTitle = $row->getData('account_title');
        $marketplaceTitle = $row->getData('marketplace_title');

        $storeModel = $this->_storeManager->getStore($row->getStoreId());
        $storeView = $this->_storeManager->getWebsite($storeModel->getWebsiteId())->getName();
        if (strtolower($storeView) != 'admin') {
            $storeView .= ' > ' . $this->_storeManager->getGroup($storeModel->getStoreGroupId())->getName();
            $storeView .= ' > ' . $storeModel->getName();
        } else {
            $storeView = __('Admin (Default Values)');
        }

        $account = __('Account');
        $marketplace = __('Marketplace');
        $store = __('Magento Store View');

        $value .= <<<HTML
<div>
    <span style="font-weight: bold">{$account}</span>: <span style="color: #505050">{$accountTitle}</span><br/>
    <span style="font-weight: bold">{$marketplace}</span>: <span style="color: #505050">{$marketplaceTitle}</span><br/>
    <span style="font-weight: bold">{$store}</span>: <span style="color: #505050">{$storeView}</span>
</div>
HTML;

        return $value;
    }

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Collection $collection
     * @param Rewrite $column
     *
     * @return void
     */
    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'main_table.title LIKE ? OR a.title LIKE ? OR m.title LIKE ?',
            '%' . $value . '%'
        );
    }

    /**
     * @ingeritdoc
     */
    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::_toHtml();
        }

        $this->jsUrl->addUrls(
            array_merge(
                $this->dataHelper->getControllerActions('Ebay\Listing'),
                $this->dataHelper->getControllerActions('Ebay_Listing_Product_Add'),
                $this->dataHelper->getControllerActions('Ebay_Log_Listing_Product'),
                $this->dataHelper->getControllerActions('Ebay\Template')
            )
        );

        $this->jsUrl->add($this->getUrl('*/listing/edit'), 'listing/edit');

        $this->jsUrl->add($this->getUrl('*/ebay_listing_edit/selectStoreView'), 'listing/selectStoreView');
        $this->jsUrl->add($this->getUrl('*/ebay_listing_edit/saveStoreView'), 'listing/saveStoreView');

        $this->jsTranslator->add('Edit Listing Title', __('Edit Listing Title'));
        $this->jsTranslator->add('Edit Listing Store View', __('Edit Listing Store View'));
        $this->jsTranslator->add('Edit Parts Compatibility Mode', __('Edit Parts Compatibility Mode'));
        $this->jsTranslator->add('Listing Title', __('Listing Title'));
        $this->jsTranslator->add(
            'The specified Title is already used for other Listing. Listing Title must be unique.',
            __(
                'The specified Title is already used for other Listing. Listing Title must be unique.'
            )
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay::class)
        );

        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Ebay/Listing/Grid',
        'M2ePro/Listing/EditTitle',
        'M2ePro/Listing/EditStoreView',
        'M2ePro/Ebay/Listing/EditCompatibilityMode'
    ], function(){
        window.EbayListingGridObj = new EbayListingGrid('{$this->getId()}');
        window.EditListingTitleObj = new ListingEditListingTitle('{$this->getId()}', '{$component}');
        window.EditListingStoreViewObj = new ListingEditListingStoreView();
        window.EditCompatibilityModeObj = new EditCompatibilityMode('{$this->getId()}');
    });
JS
        );

        return parent::_toHtml();
    }
}
