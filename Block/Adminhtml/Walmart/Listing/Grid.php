<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Grid
{
    protected $walmartFactory;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $moduleDatabaseStructure;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructure,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->walmartFactory          = $walmartFactory;
        $this->moduleDatabaseStructure = $moduleDatabaseStructure;
        parent::__construct($viewHelper, $context, $backendHelper, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingGrid');
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        // Get collection of listings
        $collection = $this->walmartFactory->getObject('Listing')->getCollection();

        // Set global filters
        // ---------------------------------------
        $filterSellingFormatTemplate = $this->getRequest()->getParam('filter_walmart_selling_format_template');
        $filterDescriptionTemplate = $this->getRequest()->getParam('filter_walmart_description_template');
        $filterSynchronizationTemplate = $this->getRequest()->getParam('filter_walmart_synchronization_template');

        if ($filterSellingFormatTemplate != 0) {
            $collection->addFieldToFilter(
                'second_table.template_selling_format_id',
                (int)$filterSellingFormatTemplate
            );
        }

        if ($filterDescriptionTemplate != 0) {
            $collection->addFieldToFilter(
                'second_table.template_description_id',
                (int)$filterDescriptionTemplate
            );
        }

        if ($filterSynchronizationTemplate != 0) {
            $collection->addFieldToFilter(
                'second_table.template_synchronization_id',
                (int)$filterSynchronizationTemplate
            );
        }
        // ---------------------------------------

        // join marketplace and accounts
        // ---------------------------------------
        $collection->getSelect()
            ->join(
                ['a'=> $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable()],
                '(`a`.`id` = `main_table`.`account_id`)',
                ['account_title'=>'title']
            )
            ->join(
                ['m'=> $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable()],
                '(`m`.`id` = `main_table`.`marketplace_id`)',
                ['marketplace_title'=>'title']
            );
        // ---------------------------------------

        $m2eproListing = $this->moduleDatabaseStructure->getTableNameWithPrefix('m2epro_listing');
        $m2eproWalmartListing = $this->moduleDatabaseStructure->getTableNameWithPrefix('m2epro_walmart_listing');
        $m2eproListingProduct = $this->moduleDatabaseStructure->getTableNameWithPrefix('m2epro_listing_product');

        $sql = "SELECT
                    l.id                                           AS listing_id,
                    COUNT(lp.id)                                   AS products_total_count,
                    COUNT(CASE WHEN lp.status = 2 THEN lp.id END)  AS products_active_count,
                    COUNT(CASE WHEN lp.status != 2 THEN lp.id END) AS products_inactive_count
                FROM `{$m2eproListing}` AS `l`
                    INNER JOIN `{$m2eproWalmartListing}` AS `wl` ON l.id = wl.listing_id
                    LEFT JOIN `{$m2eproListingProduct}` AS `lp` ON l.id = lp.listing_id
                GROUP BY listing_id";

        $collection->getSelect()->joinLeft(
            new \Zend_Db_Expr('('.$sql.')'),
            'main_table.id=t.listing_id',
            [
                'products_total_count'    => 'products_total_count',
                'products_active_count'   => 'products_active_count',
                'products_inactive_count' => 'products_inactive_count',
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function getColumnActionsItems()
    {
        $backUrl = $this->dataHelper->makeBackUrlParam(
            '*/walmart_listing/index'
        );

        return [
            'manageProducts' => [
                'caption' => $this->__('Manage'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/walmart_listing/view',
                    'params' => ['back' => $backUrl]
                ]
            ],

            'addProductsFromProductsList' => [
                'caption' => $this->__('Add From Products List'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/walmart_listing_product_add/index',
                    'params' => [
                        'back' => $backUrl,
                        'step' => 2,
                        'source' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_PRODUCT
                    ]
                ]
            ],

            'addProductsFromCategories' => [
                'caption' => $this->__('Add From Categories'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/walmart_listing_product_add/index',
                    'params' => [
                        'back' => $backUrl,
                        'step' => 2,
                        'source' => \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::MODE_CATEGORY
                    ]
                ]
            ],

            'automaticActions' => [
                'caption' => $this->__('Auto Add/Remove Rules'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/walmart_listing/view',
                    'params' => [
                        'back' => $backUrl,
                        'auto_actions' => 1
                    ]
                ]
            ],

            'editListingTitle' => [
                'caption' => $this->__('Title'),
                'group'   => 'edit_actions',
                'confirm' => $this->__('Are you sure?'),
                'field'   => 'id',
                'onclick_action' => 'EditListingTitleObj.openPopup'
            ],

            'editConfiguration' => [
                'caption' => $this->__('Configuration'),
                'group'   => 'edit_actions',
                'field'   => 'id',
                'url'     => [
                    'base'   => '*/walmart_listing/edit',
                    'params' => ['back' => $backUrl]
                ]
            ],

            'viewLog' => [
                'caption' => $this->__('Logs & Events'),
                'group'   => 'other',
                'field'   => \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD,
                'url'     => [
                    'base'   => '*/walmart_log_listing_product/index'
                ]
            ],

            'clearLogs' => [
                'caption' => $this->__('Clear Log'),
                'confirm' => $this->__('Are you sure?'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => [
                    'base' => '*/listing/clearLog',
                    'params' => [
                        'back' => $backUrl
                    ]
                ]
            ],

            'deleteListing' => [
                'caption' => $this->__('Delete Listing'),
                'confirm' => $this->__('Are you sure?'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => [
                    'base' => '*/walmart_listing/delete',
                    'params' => [
                        'back' => $backUrl
                    ]
                ]
            ],
        ];
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<span id="listing_title_'.$row->getId().'">' .
            $this->dataHelper->escapeHtml($value) .
            '</span>';

        /** @var \Ess\M2ePro\Model\Listing $row */
        $accountTitle = $row->getData('account_title');
        $marketplaceTitle = $row->getData('marketplace_title');

        $storeModel = $this->_storeManager->getStore($row->getStoreId());
        $storeView = $this->_storeManager->getWebsite($storeModel->getWebsiteId())->getName();
        if (strtolower($storeView) != 'admin') {
            $storeView .= ' > '.$this->_storeManager->getGroup($storeModel->getStoreGroupId())->getName();
            $storeView .= ' > '.$storeModel->getName();
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

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'main_table.title LIKE ? OR m.title LIKE ? OR a.title LIKE ?',
            '%'. $value .'%'
        );
    }

    //########################################

    public function getRowUrl($row)
    {
        $backUrl = $this->dataHelper->makeBackUrlParam(
            '*/walmart_listing/index'
        );

        return $this->getUrl(
            '*/walmart_listing/view',
            [
                'id' => $row->getId(),
                'back' => $backUrl
            ]
        );
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getUrl('*/listing/edit'), 'listing/edit');

        $this->jsUrl->add($this->getUrl('*/walmart_listing/saveTitle'), 'walmart_listing/saveTitle');

        $uniqueTitleTxt = 'The specified Title is already used for other Listing. Listing Title must be unique.';

        $this->jsTranslator->addTranslations([
            'Cancel' => $this->__('Cancel'),
            'Save' => $this->__('Save'),
            'Edit Listing Title' => $this->__('Edit Listing Title'),
            $uniqueTitleTxt => $this->__($uniqueTitleTxt)
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

    //########################################
}
