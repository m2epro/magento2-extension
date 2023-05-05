<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\ItemsByListing;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Grid
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    protected $walmartFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory */
    private $listingCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account */
    private $accountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product */
    private $walmartListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Helper\Url */
    private $urlHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Helper\Url $urlHelper,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->accountResource = $accountResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->walmartListingProductResource = $walmartListingProductResource;
        $this->listingProductResource = $listingProductResource;
        $this->urlHelper = $urlHelper;

        parent::__construct($viewHelper, $context, $backendHelper, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartListingGrid');
    }

    protected function _prepareCollection()
    {
        $collection = $this->listingCollectionFactory->createWithWalmartChildMode();

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

        $select->from(['wlp' => $this->walmartListingProductResource->getMainTable()], []);
        $select->joinLeft(
            ['lp' => $this->listingProductResource->getMainTable()],
            'lp.id = wlp.listing_product_id',
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
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function getColumnActionsItems()
    {
        $backUrl = $this->urlHelper->makeBackUrlParam(
            '*/walmart_listing/index'
        );

        return [
            'manageProducts' => [
                'caption' => __('Manage'),
                'group' => 'products_actions',
                'field' => 'id',
                'url' => [
                    'base' => '*/walmart_listing/view',
                    'params' => ['back' => $backUrl],
                ],
            ],

            'addProductsFromProductsList' => [
                'caption' => __('Add From Products List'),
                'group' => 'products_actions',
                'field' => 'id',
                'url' => [
                    'base' => '*/walmart_listing_product_add/index',
                    'params' => [
                        'back' => $backUrl,
                        'step' => 2,
                        'source' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_PRODUCT,
                    ],
                ],
            ],

            'addProductsFromCategories' => [
                'caption' => __('Add From Categories'),
                'group' => 'products_actions',
                'field' => 'id',
                'url' => [
                    'base' => '*/walmart_listing_product_add/index',
                    'params' => [
                        'back' => $backUrl,
                        'step' => 2,
                        'source' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_CATEGORY,
                    ],
                ],
            ],

            'automaticActions' => [
                'caption' => __('Auto Add/Remove Rules'),
                'group' => 'products_actions',
                'field' => 'id',
                'url' => [
                    'base' => '*/walmart_listing/view',
                    'params' => [
                        'back' => $backUrl,
                        'auto_actions' => 1,
                    ],
                ],
            ],

            'editListingTitle' => [
                'caption' => __('Title'),
                'group' => 'edit_actions',
                'confirm' => __('Are you sure?'),
                'field' => 'id',
                'onclick_action' => 'EditListingTitleObj.openPopup',
            ],

            'editConfiguration' => [
                'caption' => __('Configuration'),
                'group' => 'edit_actions',
                'field' => 'id',
                'url' => [
                    'base' => '*/walmart_listing/edit',
                    'params' => ['back' => $backUrl],
                ],
            ],

            'viewLog' => [
                'caption' => __('Logs & Events'),
                'group' => 'other',
                'field' => \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD,
                'url' => [
                    'base' => '*/walmart_log_listing_product/index',
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

            'deleteListing' => [
                'caption' => __('Delete Listing'),
                'confirm' => __('Are you sure?'),
                'group' => 'other',
                'field' => 'id',
                'url' => [
                    'base' => '*/walmart_listing/delete',
                    'params' => [
                        'back' => $backUrl,
                    ],
                ],
            ],
        ];
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<span id="listing_title_' . $row->getId() . '">' .
            $this->dataHelper->escapeHtml($value) .
            '</span>';

        /** @var \Ess\M2ePro\Model\Listing $row */
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

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'main_table.title LIKE ? OR m.title LIKE ? OR a.title LIKE ?',
            '%' . $value . '%'
        );
    }

    public function getRowUrl($item)
    {
        $backUrl = $this->dataHelper->makeBackUrlParam(
            '*/walmart_listing/index'
        );

        return $this->getUrl(
            '*/walmart_listing/view',
            [
                'id' => $item->getId(),
                'back' => $backUrl,
            ]
        );
    }

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getUrl('*/listing/edit'), 'listing/edit');

        $this->jsUrl->add($this->getUrl('*/walmart_listing/saveTitle'), 'walmart_listing/saveTitle');

        $uniqueTitleTxt = 'The specified Title is already used for other Listing. Listing Title must be unique.';

        $this->jsTranslator->addTranslations([
            'Cancel' => __('Cancel'),
            'Save' => __('Save'),
            'Edit Listing Title' => __('Edit Listing Title'),
            $uniqueTitleTxt => __($uniqueTitleTxt),
        ]);

        $component = \Ess\M2ePro\Helper\Component\Walmart::NICK;

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Listing/EditTitle'
    ], function(){

        window.EditListingTitleObj = new ListingEditListingTitle('{$this->getId()}', '{$component}');

    });
JS
        );

        return parent::_toHtml();
    }
}
