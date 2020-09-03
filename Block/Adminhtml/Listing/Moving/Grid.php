<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Moving;

use Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\Moving\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $storeFactory;

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->storeFactory = $storeFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingMovingGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setPagerVisibility(false);
        $this->setDefaultLimit(100);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $componentMode = $this->getHelper('Data\GlobalData')->getValue('componentMode');
        $ignoreListings = (array)$this->getHelper('Data\GlobalData')->getValue('ignoreListings');

        // Update statistic table values
        $this->activeRecordFactory->getObject('Listing')->getResource()->updateStatisticColumns();
        $this->activeRecordFactory->getObject(ucfirst($componentMode).'\Listing')
            ->getResource()->updateStatisticColumns();

        $collection = $this->parentFactory
            ->getObject($componentMode, 'Listing')
            ->getCollection();

        foreach ($ignoreListings as $listingId) {
            $collection->addFieldToFilter('main_table.id', ['neq'=>$listingId]);
        }

        $this->addAccountAndMarketplaceFilter($collection);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('listing_id', [
            'header'       => $this->__('ID'),
            'align'        => 'right',
            'type'         => 'number',
            'width'        => '75px',
            'index'        => 'id',
            'filter_index' => 'id',
            'frame_callback' => [$this, 'callbackColumnId']
        ]);

        $this->addColumn('title', [
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '200px',
            'index'        => 'title',
            'escape'       => false,
            'filter_index' => 'main_table.title',
            'frame_callback' => [$this, 'callbackColumnTitle']
        ]);

        $this->addColumn('store_name', [
            'header'        => $this->__('Store View'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '100px',
            'index'        => 'store_id',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnStore']
        ]);

        $this->addColumn('products_total_count', [
            'header'        => $this->__('Total Items'),
            'align'        => 'right',
            'type'         => 'number',
            'width'        => '100px',
            'index'        => 'products_total_count',
            'filter_index' => 'products_total_count',
            'frame_callback' => [$this, 'callbackColumnSourceTotalItems']
        ]);

        $this->addColumn('actions', [
            'header'       => $this->__('Actions'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '125px',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => [$this, 'callbackColumnActions'],
        ]);
    }

    //########################################

    public function callbackColumnId($value, $row, $column, $isExport)
    {
        return $value.'&nbsp;';
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $title = $this->getHelper('Data')->escapeHtml($value);
        $url = $this->getHelper('View')->getUrl(
            $row,
            'listing',
            'view',
            ['id' => $row->getData('id')]
        );
        return '&nbsp;<a href="'.$url.'" target="_blank">'.$title.'</a>';
    }

    public function callbackColumnStore($value, $row, $column, $isExport)
    {
        $storeModel = $this->storeFactory->create()->load($value);
        $website = $storeModel->getWebsite();

        if (!$website) {
            return '';
        }

        $websiteName = $website->getName();

        if (strtolower($websiteName) != 'admin') {
            $storeName = $storeModel->getName();
        } else {
            $storeName = $storeModel->getGroup()->getName();
        }

        return '&nbsp;'.$storeName;
    }

    public function callbackColumnSource($value, $row, $column, $isExport)
    {
        return '&nbsp;'.$value;
    }

    public function callbackColumnSourceTotalItems($value, $row, $column, $isExport)
    {
        return $value.'&nbsp;';
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $actions = <<<HTML
&nbsp;<a href="javascript:void(0);" onclick="CommonObj.confirm({
        actions: {
            confirm: function () {
                {$this->getMovingHandlerJs()}.gridHandler.tryToMove({$row->getData('id')});
            }.bind(this),
            cancel: function () {
                return false;
            }
        }
    });">{$this->__('Move To This Listing')}</a>
HTML;
        return $actions;
    }

    //########################################

    protected function getHelpBlockHtml()
    {
        $helpBlockHtml  = '';

        if ($this->canDisplayContainer()) {
            $componentTitle = $this->getHelper('Component')->getComponentTitle(
                $this->getHelper('Data\GlobalData')->getValue('componentMode')
            );

            $helpBlockHtml = $this->createBlock('HelpBlock')->setData([
                'content' => <<<HTML
                Item(s) can be moved to a Listing within the same {$componentTitle} Account and Marketplace.<br>
                You can select an existing M2E Pro Listing or create a new one.<br><br>
        
                <strong>Note:</strong> Once the Items are moved, they will be updated
                 based on the new Listing settings.
HTML
            ])->toHtml();
        }

        return $helpBlockHtml;
    }

    protected function getNewListingUrl()
    {
        $componentMode = $this->getHelper('Data\GlobalData')->getValue('componentMode');
        $newListingUrl = $this->getUrl(
            '*/' .strtolower($componentMode). '_listing_create/index',
            [
                'step'           => 1,
                'clear'          => 1,
                'account_id'     => $this->getHelper('Data\GlobalData')->getValue('accountId'),
                'marketplace_id' => $this->getHelper('Data\GlobalData')->getValue('marketplaceId'),
                'creation_mode'  => \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY,
                'component'      => $componentMode
            ]
        );

        return $newListingUrl;
    }

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getNewListingUrl(), 'add_new_listing_url');

        $this->js->add(<<<JS
        var warning_msg_block = $('empty_grid_warning');
            warning_msg_block && warning_msg_block.remove();

            $$('#listingMovingGrid div.grid th').each(function(el) {
                el.style.padding = '2px 4px';
            });

            $$('#listingMovingGrid div.grid td').each(function(el) {
                el.style.padding = '2px 4px';
            });
JS
        );

        return $this->getHelpBlockHtml() . parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getData('grid_url');
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function addAccountAndMarketplaceFilter($collection)
    {
        $accountId = $this->getHelper('Data\GlobalData')->getValue('accountId');
        $marketplaceId = $this->getHelper('Data\GlobalData')->getValue('marketplaceId');

        $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        $collection->addFieldToFilter('main_table.account_id', $accountId);
    }

    //########################################
}
