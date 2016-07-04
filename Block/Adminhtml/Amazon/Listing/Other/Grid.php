<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $cacheData = array();
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
        $collection->getSelect()->group(array('account_id','marketplace_id'));

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('account', array(
            'header'    => $this->__('Account'),
            'align'     => 'left',
            'type'      => 'text',
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnAccount')
        ));

        $this->addColumn('marketplace', array(
            'header'    => $this->__('Marketplace'),
            'align'     => 'left',
            'type'      => 'text',
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnMarketplace')
        ), 'account');

        $this->addColumn('products_total_count', array(
            'header'    => $this->__('Total Items'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'products_total_count',
            'filter_index' => 'main_table.products_total_count',
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnTotalProducts')
        ));

        $this->addColumn('products_active_count', array(
            'header'    => $this->__('Active Items'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'products_active_count',
            'filter_index' => 'main_table.products_active_count',
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnListedProducts')
        ));

        $this->addColumn('products_inactive_count', array(
            'header'    => $this->__('Inactive Items'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'products_inactive_count',
            'filter_index' => 'main_table.products_inactive_count',
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnInactiveProducts')
        ));

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
            ->getObjectLoaded('Marketplace',$row->getData('marketplace_id'))
            ->getTitle();
        return $this->getHelper('Data')->escapeHtml($marketplaceTitle);
    }

    public function callbackColumnTotalProducts($value, $row, $column, $isExport)
    {
        $accountId = $row->getAccountId();
        $marketplaceId = $row->getMarketplaceId();
        $key = $accountId . ',' . $marketplaceId;

        $value = $this->cacheData[$key]['total_items'];

        if (is_null($value) || $value === '') {
            $value = $this->__('N/A');
        } else if ($value <= 0) {
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

        if (is_null($value) || $value === '') {
            $value = $this->__('N/A');
        } else if ($value <= 0) {
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

        if (is_null($value) || $value === '') {
            $value = $this->__('N/A');
        } else if ($value <= 0) {
            $value = '<span style="color: red;">0</span>';
        }

        return $value;
    }

    //########################################

    public function getRowUrl($row)
    {
        return $this->getUrl('*/amazon_listing_other/view', array(
            'account' => $row->getData('account_id'),
            'marketplace' => $row->getData('marketplace_id'),
            'back'=> $this->getHelper('Data')->makeBackUrlParam('*/amazon_listing_other/index')
        ));
    }

    //########################################

    protected function prepareCacheData()
    {
        $this->cacheData = array();

        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            array('account_id', 'marketplace_id', 'status')
        );

        /* @var $item \Ess\M2ePro\Model\Listing\Other */
        foreach ($collection->getItems() as $item) {

            $accountId = $item->getAccountId();
            $marketplaceId = $item->getMarketplaceId();
            $key = $accountId . ',' . $marketplaceId;

            empty($this->cacheData[$key]) && ($this->cacheData[$key] = array(
                'total_items' => 0,
                'active_items' => 0,
                'inactive_items' => 0
            ));

            ++$this->cacheData[$key]['total_items'];

            if ($item->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                ++$this->cacheData[$key]['active_items'];
            } else {
                ++$this->cacheData[$key]['inactive_items'];
            }
        }
    }

    //########################################
}