<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search;

use Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty as OnlineQty;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $magentoProductCollectionFactory;
    protected $localeCurrency;
    protected $ebayFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->localeCurrency = $localeCurrency;
        $this->ebayFactory = $ebayFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    abstract protected function callbackColumnActions($value, $row, $column, $isExport);

    //----------------------------------------

    abstract protected function callbackFilterProductId($collection, $column);

    abstract protected function callbackFilterTitle($collection, $column);

    abstract protected function callbackFilterPrice($collection, $column);

    abstract protected function callbackFilterOnlineQty($collection, $column);

    abstract protected function callbackFilterStatus($collection, $column);

    abstract protected function callbackFilterItemId($collection, $column);

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'       => $this->__('Product ID'),
            'align'        => 'right',
            'width'        => '100px',
            'type'         => 'number',
            'index'        => 'entity_id',
            'filter_index' => 'entity_id',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId',
            'filter_condition_callback' => [$this, 'callbackFilterProductId']
        ]);

        $this->addColumn('name', [
            'header'         => $this->__('Product Title / Listing / Product SKU'),
            'align'          => 'left',
            'type'           => 'text',
            'index'          => 'name',
            'filter_index'   => 'name',
            'escape'         => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('item_id', [
            'header'       => $this->__('Item ID'),
            'align'        => 'left',
            'width'        => '100px',
            'type'         => 'text',
            'index'        => 'item_id',
            'filter_index' => 'item_id',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\ItemId',
            'filter_condition_callback' => [$this, 'callbackFilterItemId']
        ]);

        $this->addColumn('online_qty', [
            'header'       => $this->__('Available QTY'),
            'align'        => 'right',
            'width'        => '50px',
            'type'         => 'number',
            'index'        => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty',
            'render_online_qty' => OnlineQty::ONLINE_AVAILABLE_QTY,
            'filter_condition_callback' => [$this, 'callbackFilterOnlineQty']
        ]);

        $this->addColumn('online_qty_sold', [
            'header'       => $this->__('Sold QTY'),
            'align'        => 'right',
            'width'        => '50px',
            'type'         => 'number',
            'index'        => 'online_qty_sold',
            'filter_index' => 'online_qty_sold',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty'
        ]);

        $this->addColumn('price', [
            'header'       => $this->__('Price'),
            'align'        => 'right',
            'width'        => '50px',
            'type'         => 'number',
            'index'        => 'online_current_price',
            'filter_index' => 'online_current_price',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\CurrentPrice',
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ]);

        $statusColumn = [
            'header'       => $this->__('Status'),
            'width'        => '100px',
            'index'        => 'status',
            'filter_index' => 'status',
            'type'         => 'options',
            'sortable'     => false,
            'options'      => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN => $this->__('Listed (Hidden)'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD => $this->__('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED => $this->__('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Pending')
            ],
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Status',
            'filter_condition_callback' => [$this, 'callbackFilterStatus']
        ];

        $listingType = $this->getRequest()->getParam(
            'listing_type',
            \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO
        );

        if ($this->getHelper('View\Ebay')->isDuplicatesFilterShouldBeShown()
            && $listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO
        ) {
            $statusColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\Status';
        }

        if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
            unset($statusColumn['options'][\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]);
        }

        $this->addColumn('status', $statusColumn);

        $this->addColumn('goto_listing_item', [
            'header'   => $this->__('Manage'),
            'align'    => 'center',
            'width'    => '50px',
            'type'     => 'text',
            'filter'   => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnActions']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getData('name');
        $onlineTitle = $row->getData('online_title');

        !empty($onlineTitle) && $title = $onlineTitle;
        $value = '<div style="margin-bottom: 5px;">' . $this->getHelper('Data')->escapeHtml($title) . '</div>';

        $additionalHtml = $this->getColumnProductTitleAdditionalHtml($row);

        if (!empty($additionalHtml)) {
            $value .= $additionalHtml;
        }

        return $value;
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_search/index', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function getColumnProductTitleAdditionalHtml($row)
    {
        return '';
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
