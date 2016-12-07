<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Grid
{
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingGrid');
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        // Update statistic table values
        $this->activeRecordFactory->getObject('Amazon\Listing')->getResource()->updateStatisticColumns();

        // Get collection of listings
        $collection = $this->amazonFactory->getObject('Listing')->getCollection();

        // Set global filters
        // ---------------------------------------
        $filterSellingFormatTemplate = $this->getRequest()->getParam('filter_amazon_selling_format_template');
        $filterSynchronizationTemplate = $this->getRequest()->getParam('filter_amazon_synchronization_template');

        if ($filterSellingFormatTemplate != 0) {
            $collection->addFieldToFilter(
                'second_table.template_selling_format_id', (int)$filterSellingFormatTemplate
            );
        }

        if ($filterSynchronizationTemplate != 0) {
            $collection->addFieldToFilter(
                'second_table.template_synchronization_id', (int)$filterSynchronizationTemplate
            );
        }
        // ---------------------------------------

        // join marketplace and accounts
        // ---------------------------------------
        $collection->getSelect()
            ->join(array('a'=> $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable()),
                '(`a`.`id` = `main_table`.`account_id`)',
                array('account_title'=>'title'))
            ->join(array('m'=> $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable()),
                '(`m`.`id` = `main_table`.`marketplace_id`)',
                array('marketplace_title'=>'title'));
        // ---------------------------------------

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function getColumnActionsItems()
    {
        $backUrl = $this->getHelper('Data')->makeBackUrlParam(
            '*/amazon_listing/index'
        );

        $actions = array(
            'manageProducts' => array(
                'caption' => $this->__('Manage'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/amazon_listing/view',
                    'params' => array('back' => $backUrl)
                )
            ),

            'addProductsFromProductsList' => array(
                'caption' => $this->__('Add From Products List'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/amazon_listing_product_add/index',
                    'params' => array(
                        'back' => $backUrl,
                        'step' => 2,
                        'source' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_PRODUCT
                    )
                )
            ),

            'addProductsFromCategories' => array(
                'caption' => $this->__('Add From Categories'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/amazon_listing_product_add/index',
                    'params' => array(
                        'back' => $backUrl,
                        'step' => 2,
                        'source' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_CATEGORY
                    )
                )
            ),

            'automaticActions' => array(
                'caption' => $this->__('Auto Add/Remove Rules'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/amazon_listing/view',
                    'params' => array(
                        'back' => $backUrl,
                        'auto_actions' => 1
                    )
                )
            ),

            'viewLog' => array(
                'caption' => $this->__('View Log'),
                'group'   => 'other',
                'field'   => \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD,
                'url'     => array(
                    'base'   => '*/amazon_log_listing_product/index'
                )
            ),

            'clearLogs' => array(
                'caption' => $this->__('Clear Log'),
                'confirm' => $this->__('Are you sure?'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => array(
                    'base' => '*/listing/clearLog',
                    'params' => array(
                        'back' => $backUrl
                    )
                )
            ),

            'deleteListing' => array(
                'caption' => $this->__('Delete Listing'),
                'confirm' => $this->__('Are you sure?'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => array(
                    'base' => '*/amazon_listing/delete',
                    'params' => array(
                        'back' => $backUrl
                    )
                )
            ),

            'editListingTitle' => array(
                'caption' => $this->__('Title'),
                'group'   => 'edit_actions',
                'confirm' => $this->__('Are you sure?'),
                'field'   => 'id',
                'onclick_action' => 'EditListingTitleObj.openPopup'
            ),

            'sellingSetting' => array(
                'caption' => $this->__('Selling'),
                'group'   => 'edit_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/amazon_listing/edit',
                    'params' => array(
                        'back' => $backUrl,
                        'tab' => 'selling'
                    )
                )
            ),

            'searchSetting' => array(
                'caption' => $this->__('Search'),
                'group'   => 'edit_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/amazon_listing/edit',
                    'params' => array(
                        'back' => $backUrl,
                        'tab' => 'search'
                    )
                )
            )
        );

        return $actions;
    }

    //########################################

    public function callbackColumnSoldProducts($value, $row, $column, $isExport)
    {
        return $this->getColumnValue($value);
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<span id="listing_title_'.$row->getId().'">' .
            $this->getHelper('Data')->escapeHtml($value) .
            '</span>';

        /* @var $row \Ess\M2ePro\Model\Listing */
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

        $collection->getSelect()->where('main_table.title LIKE ? OR m.title LIKE ? OR a.title LIKE ?',
                                        '%'. $value .'%');
    }

    //########################################

    public function getRowUrl($row)
    {
        $backUrl = $this->getHelper('Data')->makeBackUrlParam(
            '*/amazon_listing/index'
        );

        return $this->getUrl(
            '*/amazon_listing/view',
            array(
                'id' => $row->getId(),
                'back' => $backUrl
            )
        );
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getUrl('*/listing/edit'), 'listing/edit');

        $this->jsUrl->add($this->getUrl('*/amazon_listing/saveTitle'), 'amazon_listing/saveTitle');

        $uniqueTitleTxt = 'The specified Title is already used for other Listing. Listing Title must be unique.';

        $this->jsTranslator->addTranslations([
            'Cancel' => $this->__('Cancel'),
            'Save' => $this->__('Save'),
            'Edit Listing Title' => $this->__('Edit Listing Title'),
            $uniqueTitleTxt => $this->__($uniqueTitleTxt)
        ]);

        $component = \Ess\M2ePro\Helper\Component\Amazon::NICK;

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