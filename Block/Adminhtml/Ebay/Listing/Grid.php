<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Grid
{
    public const MASS_ACTION_ID_EDIT_PARTS_COMPATIBILITY = 'editPartsCompatibilityMode';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory  */
    protected $ebayFactory;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $moduleDatabaseStructure;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructure,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->ebayFactory             = $ebayFactory;
        $this->moduleDatabaseStructure = $moduleDatabaseStructure;
        parent::__construct($viewHelper, $context, $backendHelper, $dataHelper,$data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingGrid');
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $aTable = $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable();
        $mTable = $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable();

        // Get collection of listings
        $collection = $this->ebayFactory->getObject('Listing')->getCollection();
        $collection->getSelect()->join(
            ['a' => $aTable],
            '(`a`.`id` = `main_table`.`account_id`)',
            ['account_title' => 'title']
        );
        $collection->getSelect()->join(
            ['m' => $mTable],
            '(`m`.`id` = `main_table`.`marketplace_id`)',
            ['marketplace_title' => 'title']
        );

        $m2eproListing = $this->moduleDatabaseStructure->getTableNameWithPrefix('m2epro_listing');
        $m2eproEbayListing = $this->moduleDatabaseStructure->getTableNameWithPrefix('m2epro_ebay_listing');
        $m2eproListingProduct = $this->moduleDatabaseStructure->getTableNameWithPrefix('m2epro_listing_product');
        $m2eproEbayListingProduct = $this->moduleDatabaseStructure
            ->getTableNameWithPrefix('m2epro_ebay_listing_product');

        $sql = "SELECT l.id AS listing_id,
                    COUNT(lp.id)                                   AS products_total_count,
                    COUNT(CASE WHEN lp.status = 2 THEN lp.id END)  AS products_active_count,
                    COUNT(CASE WHEN lp.status != 2 THEN lp.id END) AS products_inactive_count,
                    IFNULL(SUM(elp.online_qty_sold), 0)            AS items_sold_count
                FROM `{$m2eproListing}` AS `l`
                    INNER JOIN `{$m2eproEbayListing}` AS `el` ON l.id = el.listing_id
                    LEFT JOIN `{$m2eproListingProduct}` AS `lp` ON l.id = lp.listing_id
                    LEFT JOIN `{$m2eproEbayListingProduct}` AS `elp` ON lp.id = elp.listing_product_id
                GROUP BY lp.listing_id";

        $collection->getSelect()->joinLeft(
            new \Zend_Db_Expr('('.$sql.')'),
            'main_table.id=t.listing_id',
            [
                'products_total_count'    => 'products_total_count',
                'products_active_count'   => 'products_active_count',
                'products_inactive_count' => 'products_inactive_count',
                'items_sold_count'        => 'items_sold_count'
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('ebay/listing/grid.css');

        return parent::_prepareLayout();
    }

    //########################################

    protected function setColumns()
    {
        $this->addColumn(
            'items_sold_count',
            [
                'header'         => $this->__('Sold QTY'),
                'align'          => 'right',
                'type'           => 'number',
                'index'          => 'items_sold_count',
                'filter_index'   => 't.items_sold_count',
                'frame_callback' => [$this, 'callbackColumnProductsCount']
            ]
        );

        return $this;
    }

    protected function getColumnActionsItems()
    {
        $backUrl = $this->dataHelper->makeBackUrlParam('*/ebay_listing/index');

        return [
            'manageProducts' => [
                'caption' => $this->__('Manage'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/ebay_listing/view',
                    'params' => ['id' => $this->getId(), 'back' => $backUrl]
                ]
            ],

            'addProductsSourceProducts' => [
                'caption'        => $this->__('Add From Products List'),
                'group'          => 'products_actions',
                'field'          => 'id',
                'onclick_action' => 'EbayListingGridObj.addProductsSourceProductsAction',
            ],

            'addProductsSourceCategories' => [
                'caption'        => $this->__('Add From Categories'),
                'group'          => 'products_actions',
                'field'          => 'id',
                'onclick_action' => 'EbayListingGridObj.addProductsSourceCategoriesAction',
            ],

            'autoActions' => [
                'caption' => $this->__('Auto Add/Remove Rules'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/ebay_listing/view',
                    'params' => ['id' => $this->getId(), 'auto_actions' => 1]
                ]
            ],

            'editTitle' => [
                'caption'        => $this->__('Title'),
                'group'          => 'edit_actions',
                'field'          => 'id',
                'onclick_action' => 'EditListingTitleObj.openPopup',
            ],

            'editConfiguration' => [
                'caption' => $this->__('Configuration'),
                'group'   => 'edit_actions',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/ebay_listing/edit',
                    'params' => ['back' => $backUrl]
                ]
            ],

            self::MASS_ACTION_ID_EDIT_PARTS_COMPATIBILITY => [
                'caption'        => $this->__('Parts Compatibility Mode'),
                'group'          => 'edit_actions',
                'field'          => 'id',
                'onclick_action' => 'EditCompatibilityModeObj.openPopup',
                'action_id'      => self::MASS_ACTION_ID_EDIT_PARTS_COMPATIBILITY
            ],

            'viewLogs' => [
                'caption' => $this->__('Logs & Events'),
                'group'   => 'other',
                'field'   => \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD,
                'url'     => [
                    'base' => '*/ebay_log_listing_product/index'
                ]
            ],

            'clearLogs' => [
                'caption' => $this->__('Clear Log'),
                'confirm' => $this->__('Are you sure?'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/listing/clearLog',
                    'params' => [
                        'back' => $backUrl
                    ]
                ]
            ],

            'delete' => [
                'caption' => $this->__('Delete Listing'),
                'confirm' => $this->__('Are you sure?'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/ebay_listing/delete',
                    'params' => ['id' => $this->getId()]
                ]
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

    //########################################

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

        /** @var \Ess\M2ePro\Model\Listing $row */
        $accountTitle = $row->getData('account_title');
        $marketplaceTitle = $row->getData('marketplace_title');

        $storeModel = $this->_storeManager->getStore($row->getStoreId());
        $storeView = $this->_storeManager->getWebsite($storeModel->getWebsiteId())->getName();
        if (strtolower($storeView) != 'admin') {
            $storeView .= ' > ' . $this->_storeManager->getGroup($storeModel->getStoreGroupId())->getName();
            $storeView .= ' > ' . $storeModel->getName();
        } else {
            $storeView = $this->__('Admin (Default Values)');
        }

        $account = $this->__('Account');
        $marketplace = $this->__('Marketplace');
        $store = $this->__('Magento Store View');

        $value .= <<<HTML
<div>
    <span style="font-weight: bold">{$account}</span>: <span style="color: #505050">{$accountTitle}</span><br/>
    <span style="font-weight: bold">{$marketplace}</span>: <span style="color: #505050">{$marketplaceTitle}</span><br/>
    <span style="font-weight: bold">{$store}</span>: <span style="color: #505050">{$storeView}</span>
</div>
HTML;

        return $value;
    }

    //########################################

    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/ebay_listing/view',
            [
                'id' => $row->getId()
            ]
        );
    }

    //########################################

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

    //########################################

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

        $this->jsTranslator->add('Edit Listing Title', $this->__('Edit Listing Title'));
        $this->jsTranslator->add('Edit Parts Compatibility Mode', $this->__('Edit Parts Compatibility Mode'));
        $this->jsTranslator->add('Listing Title', $this->__('Listing Title'));
        $this->jsTranslator->add(
            'The specified Title is already used for other Listing. Listing Title must be unique.',
            $this->__(
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
        'M2ePro/Ebay/Listing/EditCompatibilityMode'
    ], function(){
        window.EbayListingGridObj = new EbayListingGrid('{$this->getId()}');
        window.EditListingTitleObj = new ListingEditListingTitle('{$this->getId()}', '{$component}');
        window.EditCompatibilityModeObj = new EditCompatibilityMode('{$this->getId()}');
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
