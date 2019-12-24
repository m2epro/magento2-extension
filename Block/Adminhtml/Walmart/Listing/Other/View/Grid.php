<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Other\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Other\View\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $localeCurrency;
    protected $resourceConnection;
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
        $this->walmartFactory = $walmartFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingOtherGrid');
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
        $collection = $this->walmartFactory->getObject('Listing\Other')->getCollection();

        $collection->getSelect()->joinLeft(
            ['mp' => $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable()],
            'mp.id = main_table.marketplace_id',
            ['marketplace_title' => 'mp.title']
        )->joinLeft(
            ['am' => $this->activeRecordFactory->getObject('Walmart\Marketplace')->getResource()->getMainTable()],
            'am.marketplace_id = main_table.marketplace_id',
            ['currency' => 'am.default_currency']
        );

        // Add Filter By Account
        if ($this->getRequest()->getParam('account')) {
            $collection->addFieldToFilter(
                'main_table.account_id',
                $this->getRequest()->getParam('account')
            );
        }

        // Add Filter By Marketplace
        if ($this->getRequest()->getParam('marketplace')) {
            $collection->addFieldToFilter(
                'main_table.marketplace_id',
                $this->getRequest()->getParam('marketplace')
            );
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => $this->__('Product ID'),
            'align'  => 'left',
            'width'  => '80px',
            'type'   => 'number',
            'index'  => 'product_id',
            'filter_index' => 'product_id',
            'frame_callback' => [$this, 'callbackColumnProductId'],
//            'filter'   => 'M2ePro/adminhtml_grid_column_filter_productId',
            'filter_condition_callback' => [$this, 'callbackFilterProductId']
        ]);

        $this->addColumn('title', [
            'header'    => $this->__('Title / SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'filter_index' => 'second_table.title',
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('gtin', [
            'header' => $this->__('GTIN'),
            'align' => 'left',
            'width' => '160px',
            'type' => 'text',
            'index' => 'gtin',
            'filter_index' => 'gtin',
            'frame_callback' => [$this, 'callbackColumnGtin'],
            'filter_condition_callback' => [$this, 'callbackFilterGtin']
        ]);

        $this->addColumn('online_qty', [
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '160px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => [$this, 'callbackColumnAvailableQty'],
            'filter_condition_callback' => [$this, 'callbackFilterQty']
        ]);

        $priceColumn = [
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '160px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ];

        $this->addColumn('online_price', $priceColumn);

        $this->addColumn('status', [
            'header' => $this->__('Status'),
            'width' => '170px',
            'index' => 'status',
            'filter_index' => 'main_table.status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus']
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
            'mapping' => $this->__('Mapping'),
            'other'   => $this->__('Other')
        ]);

        // Set mass-action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem('autoMapping', [
            'label'   => $this->__('Map Item(s) Automatically'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ], 'mapping');
        $this->getMassactionBlock()->addItem('moving', [
            'label'   => $this->__('Move Item(s) to Listing'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ], 'other');
        $this->getMassactionBlock()->addItem('removing', [
            'label'   => $this->__('Remove Item(s)'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ], 'other');
        $this->getMassactionBlock()->addItem('unmapping', [
            'label'   => $this->__('Unmap Item(s)'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
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
                                    onclick="WalmartListingOtherMappingObj.openPopUp(\''.
                         $productTitle.
                         '\','.
                         (int)$row->getId().
                         ');">' . $this->__('Map') . '</a>';

            if ($this->getHelper('Module')->isDevelopmentMode()) {
                $htmlValue .= '<br/>' . $row->getId();
            }
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
                      .' onclick="WalmartListingOtherGridObj.movingHandler.getGridHtml('
                      .$this->getHelper('Data')->jsonEncode([(int)$row->getData('id')])
                      .')">'
                      .$this->__('Move')
                      .'</a>';

        if ($this->getHelper('Module')->isDevelopmentMode()) {
            $htmlValue .= '<br/>' . $row->getId();
        }

        return $htmlValue;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getChildObject()->getData('title');

        if ($title === null) {
            $value = '<i style="color:gray;">receiving...</i>';
        } else {
            $value = '<span>' .$this->escapeHtml($title). '</span>';
        }

        $tempSku = $row->getChildObject()->getData('sku');
        empty($tempSku) && $tempSku = $this->__('N/A');

        $value .= '<br/><strong>'
                  .$this->__('SKU')
                  .':</strong> '
                  .$this->escapeHtml($tempSku);

        return $value;
    }

    public function callbackColumnGtin($gtin, $row, $column, $isExport)
    {
        $childObject = $row->getChildObject();
        $gtin = $childObject->getData('gtin');

        if (empty($gtin)) {
            return $this->__('N/A');
        }

        $gtinHtml = $this->escapeHtml($gtin);
        $channelUrl = $childObject->getData('channel_url');

        if (!empty($channelUrl)) {
            $gtinHtml = <<<HTML
<a href="{$channelUrl}" target="_blank">{$gtin}</a>
HTML;
        }

        $html = <<<HTML
<div class="walmart-identifiers-gtin" style="display: inline-block">{$gtinHtml}</div>
HTML;

        $identifiers = [
            'UPC'        => $childObject->getData('upc'),
            'EAN'        => $childObject->getData('ean'),
            'ISBN'       => $childObject->getData('isbn'),
            'Walmart ID' => $childObject->getData('wpid'),
            'Item ID'    => $childObject->getData('item_id')
        ];

        $htmlAdditional = '';
        foreach ($identifiers as $title => $value) {
            if (empty($value)) {
                continue;
            }

            if (($childObject->getData('upc') || $childObject->getData('ean') || $childObject->getData('isbn')) &&
                ($childObject->getData('wpid') || $childObject->getData('item_id')) && $title == 'Walmart ID') {
                $htmlAdditional .= "<div class='separator-line'></div>";
            }
            $identifierCode  = $this->__($title);
            $identifierValue = $this->escapeHtml($value);

            $htmlAdditional .= <<<HTML
<div>
    <span style="display: inline-block; float: left;">
        <strong>{$identifierCode}:</strong>&nbsp;&nbsp;&nbsp;&nbsp;
    </span>
    <span style="display: inline-block; float: right;">
        {$identifierValue}
    </span>
    <div style="clear: both;"></div>
</div>
HTML;
        }

        if ($htmlAdditional != '') {
            $html .= <<<HTML
&nbsp;<div class="fix-magento-tooltip" style="display: inline-block">
    {$this->getTooltipHtml($htmlAdditional)}
</div>
HTML;
        }

        return $html;
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('online_qty');
        if ($value === null || $value === '' ||
            ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
             !$row->getData('is_online_price_invalid'))) {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('online_price');
        if ($value === null || $value === '' ||
            ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
             !$row->getData('is_online_price_invalid'))) {
            return $this->__('N/A');
        }

        $currency = $this->walmartFactory
                        ->getObjectLoaded('Marketplace', $row->getData('marketplace_id'))
                        ->getChildObject()
                        ->getDefaultCurrency();

        $priceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($value);

        return $priceValue;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        switch ($row->getData('status')) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $value = '<span style="color: green;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $value = '<span style="color: red;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $value = '<span style="color: orange; font-weight: bold;">' . $value . '</span>';
                break;

            default:
                break;
        }

        /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
        $listingOther = $this->walmartFactory
                            ->getObjectLoaded('Listing\Other', $row->getData('id'));

        $statusChangeReasons = $listingOther->getChildObject()->getStatusChangeReasons();

        return $value.$this->getStatusChangeReasons($statusChangeReasons).$this->getViewLogIconHtml($row->getId());
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

        $collection->getSelect()->where('second_table.title LIKE ? OR second_table.sku LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= 'online_qty >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $where .= 'online_qty <= ' . (int)$value['to'];
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterGtin($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = <<<SQL
gtin LIKE '%{$value}%' OR
upc LIKE '%{$value}%' OR
wpid LIKE '%{$value}%' OR
item_id LIKE '%{$value}%'
SQL;

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= 'online_price >= ' . (float)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $where .= 'online_price <= ' . (float)$value['to'];
        }

        $collection->getSelect()->where($where);
    }

    //########################################

    private function getStatusChangeReasons($statusChangeReasons)
    {
        if (empty($statusChangeReasons)) {
            return '';
        }

        $html = '<li style="margin-bottom: 5px;">'
                . implode('</li><li style="margin-bottom: 5px;">', $statusChangeReasons)
                . '</li>';

        return <<<HTML
        <div style="display: inline-block; width: 16px; margin-left: 3px;" class="fix-magento-tooltip">
            {$this->getTooltipHtml($html)}
        </div>
HTML;
    }

    public function getViewLogIconHtml($listingOtherId)
    {
        $listingOtherId = (int)$listingOtherId;
        $availableActionsId = array_keys($this->getAvailableActions());

        // Get last messages
        // ---------------------------------------
        $connection = $this->resourceConnection->getConnection();

        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Listing_Other_Log')->getResource()->getMainTable(),
                ['action_id','action','type','description','create_date','initiator']
            )
            ->where('`listing_other_id` = ?', $listingOtherId)
            ->where('`action` IN (?)', $availableActionsId)
            ->order(['id DESC'])
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        $logs = $connection->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        // ---------------------------------------

        $summary = $this->createBlock('Listing_Log_Grid_LastActions')->setData([
            'entity_id' => $listingOtherId,
            'logs'      => $logs,
            'available_actions' => $this->getAvailableActions(),
            'view_help_handler' => 'WalmartListingOtherGridObj.viewItemHelp',
            'hide_help_handler' => 'WalmartListingOtherGridObj.hideItemHelp',
        ]);

        return $summary->toHtml();
    }

    private function getAvailableActions()
    {
        return [
            \Ess\M2ePro\Model\Listing\Other\Log::ACTION_CHANNEL_CHANGE => $this->__('Channel Change')
        ];
    }

    //########################################

    protected function _beforeToHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getParam('isAjax')) {
            $this->js->addRequireJs([
                'jQuery' => 'jquery'
            ], <<<JS

            WalmartListingOtherGridObj.afterInitPage();
JS
            );
        }

        return parent::_beforeToHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_listing_other/grid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}
