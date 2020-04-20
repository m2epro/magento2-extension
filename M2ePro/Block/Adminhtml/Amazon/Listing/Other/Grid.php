<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $cacheData = [];
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingOtherGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setSaveParametersInSession(true);
        $this->setPagerVisibility(false);
        $this->setUseAjax(true);
        $this->setFilterVisibility(false);
        $this->setDefaultLimit(100);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $this->prepareCacheData();

        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->group(['account_id','marketplace_id']);

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('account', [
            'header'    => $this->__('Account'),
            'align'     => 'left',
            'type'      => 'text',
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnAccount']
        ]);

        $this->addColumn('marketplace', [
            'header'    => $this->__('Marketplace'),
            'align'     => 'left',
            'type'      => 'text',
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnMarketplace']
        ]);

        $this->addColumn('products_total_count', [
            'header'    => $this->__('Total Items'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'products_total_count',
            'filter_index' => 'main_table.products_total_count',
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnTotalProducts']
        ]);

        $this->addColumn('products_active_count', [
            'header'    => $this->__('Active Items'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'products_active_count',
            'filter_index' => 'main_table.products_active_count',
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnListedProducts']
        ]);

        $this->addColumn('products_inactive_count', [
            'header'    => $this->__('Inactive Items'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'products_inactive_count',
            'filter_index' => 'main_table.products_inactive_count',
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnInactiveProducts']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnAccount($value, $row, $column, $isExport)
    {
        $accountTitle = $this->activeRecordFactory
            ->getObjectLoaded('Account', $row->getData('account_id'))
            ->getTitle();
        return $this->getHelper('Data')->escapeHtml($accountTitle);
    }

    public function callbackColumnMarketplace($value, $row, $column, $isExport)
    {
        $marketplaceTitle = $this->amazonFactory
            ->getObjectLoaded('Marketplace', $row->getData('marketplace_id'))
            ->getTitle();
        return $this->getHelper('Data')->escapeHtml($marketplaceTitle);
    }

    public function callbackColumnTotalProducts($value, $row, $column, $isExport)
    {
        $accountId = $row->getAccountId();
        $marketplaceId = $row->getMarketplaceId();
        $key = $accountId . ',' . $marketplaceId;

        $value = $this->cacheData[$key]['total_items'];

        if ($value === null || $value === '') {
            $value = $this->__('N/A');
        } elseif ($value <= 0) {
            $value = '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnListedProducts($value, $row, $column, $isExport)
    {
        $accountId = $row->getAccountId();
        $marketplaceId = $row->getMarketplaceId();
        $key = $accountId . ',' . $marketplaceId;

        $value = $this->cacheData[$key]['active_items'];

        if ($value === null || $value === '') {
            $value = $this->__('N/A');
        } elseif ($value <= 0) {
            $value = '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnInactiveProducts($value, $row, $column, $isExport)
    {
        $accountId = $row->getAccountId();
        $marketplaceId = $row->getMarketplaceId();
        $key = $accountId . ',' . $marketplaceId;

        $value = $this->cacheData[$key]['inactive_items'];

        if ($value === null || $value === '') {
            $value = $this->__('N/A');
        } elseif ($value <= 0) {
            $value = '<span style="color: red;">0</span>';
        }

        return $value;
    }

    //########################################

    public function getRowUrl($row)
    {
        return $this->getUrl('*/amazon_listing_other/view', [
            'account' => $row->getData('account_id'),
            'marketplace' => $row->getData('marketplace_id'),
            'back'=> $this->getHelper('Data')->makeBackUrlParam('*/amazon_listing_other/index')
        ]);
    }

    //########################################

    protected function prepareCacheData()
    {
        $this->cacheData = [];

        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns([
            'count' => new \Zend_Db_Expr('COUNT(id)'),
            'account_id',
            'marketplace_id',
            'status'
        ]);
        $collection->getSelect()->group(['account_id','marketplace_id','status']);

        foreach ($collection->getItems() as $item) {
            $key = $item->getData('account_id') . ',' . $item->getData('marketplace_id');

            empty($this->cacheData[$key]) && ($this->cacheData[$key] = [
                'total_items' => 0,
                'active_items' => 0,
                'inactive_items' => 0
            ]);

            if ($item->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $this->cacheData[$key]['active_items'] += (int)$item['count'];
            } else {
                $this->cacheData[$key]['inactive_items'] += (int)$item['count'];
            }
            $this->cacheData[$key]['total_items'] += (int)$item['count'];
        }
    }

    //########################################
}
