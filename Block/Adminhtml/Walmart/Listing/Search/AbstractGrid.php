<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Search\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $magentoProductCollectionFactory;
    protected $localeCurrency;
    protected $walmartFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->localeCurrency = $localeCurrency;
        $this->walmartFactory = $walmartFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    abstract public function callbackColumnProductTitle($value, $row, $column, $isExport);
    abstract public function callbackColumnStatus($value, $row, $column, $isExport);
    abstract public function callbackColumnActions($value, $row, $column, $isExport);
    abstract public function callbackColumnPrice($value, $row, $column, $isExport);

    //----------------------------------------

    abstract protected function callbackFilterProductId($collection, $column);
    abstract protected function callbackFilterTitle($collection, $column);
    abstract protected function callbackFilterOnlineSku($collection, $column);
    abstract protected function callbackFilterGtin($collection, $column);
    abstract protected function callbackFilterPrice($collection, $column);
    abstract protected function callbackFilterQty($collection, $column);
    abstract protected function callbackFilterStatus($collection, $column);

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', [
            'header'   => $this->__('Product ID'),
            'align'    => 'right',
            'width'    => '100px',
            'type'     => 'number',
            'index'    => 'entity_id',
            'filter_index' => 'entity_id',
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId',
            'filter_condition_callback' => [$this, 'callbackFilterProductId']
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title / Listing / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'escape'       => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('online_sku', [
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'online_sku',
            'filter_index' => 'online_sku',
            'show_edit_sku' => false,
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Sku',
            'filter_condition_callback' => [$this, 'callbackFilterOnlineSku']
        ]);

        $this->addColumn('gtin', [
            'header' => $this->__('GTIN'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'gtin',
            'show_edit_identifier' => false,
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Gtin',
            'filter_index' => 'gtin',
            'filter_condition_callback' => [$this, 'callbackFilterGtin']
        ]);

        $this->addColumn('online_qty', [
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Qty',
            'filter_condition_callback' => [$this, 'callbackFilterQty']
        ]);

        $this->addColumn('online_price', [
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '110px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ]);

        $statusColumn = [
            'header' => $this->__('Status'),
            'width' => '125px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus']
        ];

        $listingType = $this->getRequest()->getParam(
            'listing_type',
            \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO
        );

        if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
            unset($statusColumn['options'][\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]);
        }

        $this->addColumn('status', $statusColumn);

        $this->addColumn('goto_listing_item', [
            'header'    => $this->__('Manage'),
            'align'     => 'center',
            'width'     => '50px',
            'type'      => 'text',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnActions']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    protected function getProductStatus($status)
    {
        switch ($status) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                return '<span style="color: green;">' . $this->__('Active') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                return'<span style="color: red;">' . $this->__('Inactive') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                return'<span style="color: orange; font-weight: bold;">' .
                $this->__('Inactive (Blocked)') . '</span>';
        }

        return '';
    }

    //########################################

    protected function getStatusChangeReasons($statusChangeReasons)
    {
        if (empty($statusChangeReasons)) {
            return '';
        }

        $html = '<li style="margin-bottom: 5px;">'
            . implode('</li><li style="margin-bottom: 5px;">', $statusChangeReasons)
            . '</li>';

        return <<<HTML
        <div style="display: inline-block; width: 16px; margin-left: 3px; margin-right: 4px;">
            {$this->getTooltipHtml($html)}
        </div>
HTML;
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_listing_search/index', ['_current'=>true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    protected function isFilterOrSortByPriceIsUsed($filterName = null, $advancedFilterName = null)
    {
        if ($filterName) {
            $filters = $this->getParam($this->getVarNameFilter());
            is_string($filters) && $filters = $this->_backendHelper->prepareFilterString($filters);

            if (is_array($filters) && array_key_exists($filterName, $filters)) {
                return true;
            }

            $sort = $this->getParam($this->getVarNameSort());
            if ($sort == $filterName) {
                return true;
            }
        }

        return false;
    }

    //########################################
}
