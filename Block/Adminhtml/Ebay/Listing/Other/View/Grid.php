<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other\View;

use Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty as OnlineQty;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other\View\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $localeCurrency;
    protected $resourceConnection;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingOtherViewGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->ebayFactory->getObject('Listing\Other')->getCollection();

        $collection->getSelect()->joinLeft(
            ['mp' => $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable()],
            'mp.id = main_table.marketplace_id',
            ['marketplace_title' => 'mp.title']
        );

        $collection->getSelect()->joinLeft(
            ['mea' => $this->activeRecordFactory->getObject('Ebay\Account')->getResource()->getMainTable()],
            'mea.account_id = main_table.account_id',
            ['account_mode' => 'mea.mode']
        );

        // Add Filter By Account
        if ($accountId = $this->getRequest()->getParam('account')) {
            $collection->addFieldToFilter('main_table.account_id', $accountId);
        }

        // Add Filter By Marketplace
        if ($marketplaceId = $this->getRequest()->getParam('marketplace')) {
            $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        }

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'id'                   => 'main_table.id',
                'account_id'           => 'main_table.account_id',
                'marketplace_id'       => 'main_table.marketplace_id',
                'product_id'           => 'main_table.product_id',
                'title'                => 'second_table.title',
                'sku'                  => 'second_table.sku',
                'item_id'              => 'second_table.item_id',
                'available_qty'        => new \Zend_Db_Expr(
                    '(second_table.online_qty - second_table.online_qty_sold)'
                ),
                'online_qty_sold'      => 'second_table.online_qty_sold',
                'online_price'         => 'second_table.online_price',
                'online_main_category' => 'second_table.online_main_category',
                'status'               => 'main_table.status',
                'start_date'           => 'second_table.start_date',
                'end_date'             => 'second_table.end_date',
                'currency'             => 'second_table.currency',
                'account_mode'         => 'mea.mode'
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'         => $this->__('Product ID'),
            'align'          => 'left',
            'type'           => 'number',
            'width'          => '80px',
            'index'          => 'product_id',
            'filter_index'   => 'main_table.product_id',
            'frame_callback' => [$this, 'callbackColumnProductId'],
            'filter'         => 'Ess\M2ePro\Block\Adminhtml\Grid\Column\Filter\ProductId',
            'filter_condition_callback' => [$this, 'callbackFilterProductId']
        ]);

        $this->addColumn('title', [
            'header'                    => $this->__('Product Title / Product SKU / eBay Category'),
            'align'                     => 'left',
            'type'                      => 'text',
            'index'                     => 'title',
            'escape'                    => false,
            'filter_index'              => 'second_table.title',
            'frame_callback'            => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('item_id', [
            'header'         => $this->__('Item ID'),
            'align'          => 'left',
            'width'          => '100px',
            'type'           => 'text',
            'index'          => 'item_id',
            'filter_index'   => 'second_table.item_id',
            'frame_callback' => [$this, 'callbackColumnItemId']
        ]);

        $this->addColumn('available_qty', [
            'header'       => $this->__('Available QTY'),
            'align'        => 'right',
            'width'        => '50px',
            'type'         => 'number',
            'index'        => 'available_qty',
            'filter_index' => new \Zend_Db_Expr('(second_table.online_qty - second_table.online_qty_sold)'),
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty',
            'render_online_qty' => OnlineQty::ONLINE_AVAILABLE_QTY
        ]);

        $this->addColumn('online_qty_sold', [
            'header'       => $this->__('Sold QTY'),
            'align'        => 'right',
            'width'        => '50px',
            'type'         => 'number',
            'index'        => 'online_qty_sold',
            'filter_index' => 'second_table.online_qty_sold',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty'
        ]);

        $this->addColumn('online_price', [
            'header'         => $this->__('Price'),
            'align'          => 'right',
            'width'          => '50px',
            'type'           => 'number',
            'index'          => 'online_price',
            'filter_index'   => 'second_table.online_price',
            'frame_callback' => [$this, 'callbackColumnOnlinePrice']
        ]);

        $this->addColumn('status', [
            'header'       => $this->__('Status'),
            'width'        => '100px',
            'index'        => 'status',
            'filter_index' => 'main_table.status',
            'type'         => 'options',
            'sortable'     => false,
            'options'      => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED   => $this->__('Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN   => $this->__('Listed (Hidden)'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD     => $this->__('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED  => $this->__('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED => $this->__('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED  => $this->__('Pending')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus']
        ]);

        $this->addColumn('end_date', [
           'header'       => $this->__('End Date'),
           'align'        => 'right',
           'width'        => '150px',
           'type'         => 'datetime',
           'filter'       => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
           'filter_time'  => true,
           'index'        => 'end_date',
           'filter_index' => 'second_table.end_date',
           'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\DateTime'
        ]);

        $back = $this->getHelper('Data')->makeBackUrlParam('*/ebay_listing_other/view', [
            'account' => $this->getRequest()->getParam('account'),
            'marketplace' => $this->getRequest()->getParam('marketplace'),
            'back' => $this->getRequest()->getParam('back')
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set mass-action identifiers
        // ---------------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        $this->getMassactionBlock()->setGroups([
            'mapping' => $this->__('Linking'),
            'other' => $this->__('Other')
        ]);

        $this->getMassactionBlock()->addItem('autoMapping', [
            'label'   => $this->__('Link Item(s) Automatically'),
            'url'     => ''
        ], 'mapping');

        $this->getMassactionBlock()->addItem('moving', [
            'label'   => $this->__('Move Item(s) to Listing'),
            'url'     => ''
        ], 'other');
        $this->getMassactionBlock()->addItem('removing', [
            'label'   => $this->__('Remove Item(s)'),
            'url'     => ''
        ], 'other');
        $this->getMassactionBlock()->addItem('unmapping', [
            'label'   => $this->__('Unlink Item(s)'),
            'url'     => ''
        ], 'mapping');
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/other/view/grid.css');

        return parent::_prepareLayout();
    }

    //########################################

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            $productTitle = $row->getChildObject()->getData('title');
            if (strlen($productTitle) > 60) {
                $productTitle = substr($productTitle, 0, 60) . '...';
            }
            $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);
            $productTitle = $this->getHelper('Data')->escapeJs($productTitle);

            $htmlValue = '&nbsp;<a href="javascript:void(0);"
                                    onclick="ListingOtherMappingObj.openPopUp(
                                    '. (int)$row->getId(). ',
                                    \''. $productTitle. '\'
                                    );">' . $this->__('Link') . '</a>';

            return $htmlValue;
        }

        $htmlValue = '&nbsp<a href="'
            .$this->getUrl(
                'catalog/product/edit',
                ['id' => $row->getData('product_id')]
            )
            .'" target="_blank">'
            .$row->getData('product_id')
            .'</a>';

        $htmlValue .= '&nbsp&nbsp&nbsp<a href="javascript:void(0);"'
            .' onclick="EbayListingOtherGridObj.movingHandler.getGridHtml('
            .$this->getHelper('Data')->jsonEncode([(int)$row->getData('id')])
            .')">'
            .$this->__('Move')
            .'</a>';

        return $htmlValue;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getChildObject()->getData('title');

        $tempSku = $row->getChildObject()->getData('sku');
        if ($tempSku === null) {
            $tempSku = '<i style="color:gray;">receiving...</i>';
        } elseif ($tempSku == '') {
            $tempSku = '<i style="color:gray;">none</i>';
        } else {
            $tempSku = $this->getHelper('Data')->escapeHtml($tempSku);
        }

        $categoryHtml = '';
        if ($category = $row->getChildObject()->getData('online_main_category')) {
            $categoryHtml = <<<HTML
<strong>{$this->__('Category')}:</strong>&nbsp;
{$this->escapeHtml($category)}
HTML;
        }

        return <<<HTML
<span>{$this->escapeHtml($title)}</span><br/>
<strong>{$this->__('SKU')}:</strong>&nbsp;{$tempSku}<br/>
{$categoryHtml}
HTML;
    }

    public function callbackColumnItemId($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('item_id');
        if (empty($value)) {
            return $this->__('N/A');
        }

        $url = $this->getHelper('Component\Ebay')->getItemUrl(
            $row->getChildObject()->getData('item_id'),
            $row->getData('account_mode'),
            $row->getData('marketplace_id')
        );
        $value = '<a href="' . $url . '" target="_blank">' . $value . '</a>';

        return $value;
    }

    public function callbackColumnOnlinePrice($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('online_price');
        if ($value === null || $value === '') {
            return $this->__('N/A');
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        return $this->localeCurrency->getCurrency($row->getChildObject()->getData('currency'))->toCurrency($value);
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $coloredStstuses = [
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => 'green',
            \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN => 'red',
            \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD => 'brown',
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => 'red',
            \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED => 'blue',
            \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => 'orange'
        ];

        $status = $row->getData('status');

        if ($status !== null && isset($coloredStstuses[$status])) {
            $value = '<span style="color: '.$coloredStstuses[$status].';">' . $value . '</span>';
        }

        return $value.$this->getLockedTag($row);
    }

    public function callbackColumnStartTime($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            return $this->__('N/A');
        }

        return $value;
    }

    //########################################

    protected function callbackFilterProductId($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= 'product_id >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }

            $where .= 'product_id <= ' . (int)$value['to'];
        }

        if (isset($value['is_mapped']) && $value['is_mapped'] !== '') {
            if (!empty($where)) {
                $where = '(' . $where . ') AND ';
            }

            if ($value['is_mapped']) {
                $where .= 'product_id IS NOT NULL';
            } else {
                $where .= 'product_id IS NULL';
            }
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'second_table.title LIKE ? OR
             second_table.sku LIKE ? OR
              second_table.online_main_category LIKE ?',
            '%'.$value.'%'
        );
    }

    //########################################

    private function getLockedTag($row)
    {
        /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
        $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', (int)$row['id']);
        $processingLocks = $listingOther->getProcessingLocks();

        $html = '';

        foreach ($processingLocks as $processingLock) {
            switch ($processingLock->getTag()) {
                case 'relist_action':
                    $html .= '<br/><span style="color: #605fff">[Relist in Progress...]</span>';
                    break;

                case 'revise_action':
                    $html .= '<br/><span style="color: #605fff">[Revise in Progress...]</span>';
                    break;

                case 'stop_action':
                    $html .= '<br/><span style="color: #605fff">[Stop in Progress...]</span>';
                    break;

                default:
                    break;
            }
        }

        return $html;
    }

    //########################################

    protected function _beforeToHtml()
    {

        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getParam('isAjax')) {
            $this->js->addRequireJs([
                'jQuery' => 'jquery'
            ], <<<JS

            EbayListingOtherGridObj.afterInitPage();
JS
            );
        }

        return parent::_beforeToHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_other/viewGrid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}
