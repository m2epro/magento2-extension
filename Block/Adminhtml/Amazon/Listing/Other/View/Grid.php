<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other\View;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $localeCurrency;
    protected $resourceConnection;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
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
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();

        $collection->getSelect()->joinLeft(
            array('mp' => $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable()),
            'mp.id = main_table.marketplace_id',
            array('marketplace_title' => 'mp.title')
        )->joinLeft(
            array('am' => $this->activeRecordFactory->getObject('Amazon\Marketplace')->getResource()->getMainTable()),
            'am.marketplace_id = main_table.marketplace_id',
            array('currency' => 'am.default_currency'));

        // Add Filter By Account
        if ($this->getRequest()->getParam('account')) {
            $collection->addFieldToFilter('main_table.account_id',
                                          $this->getRequest()->getParam('account'));
        }

        // Add Filter By Marketplace
        if ($this->getRequest()->getParam('marketplace')) {
            $collection->addFieldToFilter('main_table.marketplace_id',
                                          $this->getRequest()->getParam('marketplace'));
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header' => $this->__('Product ID'),
            'align'  => 'left',
            'width'  => '80px',
            'type'   => 'number',
            'index'  => 'product_id',
            'filter_index' => 'product_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('title', array(
            'header'    => $this->__('Title / SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'filter_index' => 'second_table.title',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('general_id', array(
            'header' => $this->__('ASIN / ISBN'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'text',
            'index' => 'general_id',
            'filter_index' => 'general_id',
            'frame_callback' => array($this, 'callbackColumnGeneralId')
        ));

        $this->addColumn('online_qty', array(
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => array($this, 'callbackColumnAvailableQty'),
            'filter'   => 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Qty',
            'filter_condition_callback' => array($this, 'callbackFilterQty')
        ));

        $priceColumn = array(
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '110px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => array($this, 'callbackColumnPrice'),
            'filter_condition_callback' => array($this, 'callbackFilterPrice')
        );

        $account = $this->amazonFactory->getObjectLoaded('Account', $this->getRequest()->getParam('account'));

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $account->getChildObject()->isRepricing()) {
            $priceColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price';
        }

        $this->addColumn('online_price', $priceColumn);

        $this->addColumn('status', array(
            'header' => $this->__('Status'),
            'width' => '75px',
            'index' => 'status',
            'filter_index' => 'main_table.status',
            'type' => 'options',
            'sortable' => false,
            'options' => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => $this->__('Unknown'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set mass-action identifiers
        // ---------------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        $this->getMassactionBlock()->setGroups(array(
            'mapping' => $this->__('Mapping'),
            'other'   => $this->__('Other')
        ));

        // Set mass-action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem('autoMapping', array(
            'label'   => $this->__('Map Item(s) Automatically'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ), 'mapping');
        $this->getMassactionBlock()->addItem('moving', array(
            'label'   => $this->__('Move Item(s) to Listing'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ), 'other');
        $this->getMassactionBlock()->addItem('removing', array(
            'label'   => $this->__('Remove Item(s)'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ), 'other');
        $this->getMassactionBlock()->addItem('unmapping', array(
            'label'   => $this->__('Unmap Item(s)'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ), 'mapping');
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
            $productTitle = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('title'));
            $productTitle = $this->getHelper('Data')->escapeJs($productTitle);
            if (strlen($productTitle) > 60) {
                $productTitle = substr($productTitle, 0, 60) . '...';
            }
            $htmlValue = '&nbsp;<a href="javascript:void(0);"
                                    onclick="AmazonListingOtherMappingObj.openPopUp(\''.
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
                     .$this->getUrl('catalog/product/edit',
                                    array('id' => $row->getData('product_id')))
                     .'" target="_blank">'
                     .$row->getData('product_id')
                     .'</a>';

        $htmlValue .= '&nbsp&nbsp&nbsp<a href="javascript:void(0);"'
                      .' onclick="AmazonListingOtherGridObj.movingHandler.getGridHtml('
                      .$this->getHelper('Data')->jsonEncode(array((int)$row->getData('id')))
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

        if (is_null($title)) {
            $title = '<i style="color:gray;">' . $this->__('receiving') . '...</i>';
        } else {
            $title = '<span>' . $this->getHelper('Data')->escapeHtml($title) . '</span>';
        }

        $tempSku = $row->getChildObject()->getData('sku');
        empty($tempSku) && $tempSku = $this->__('N/A');

        $title .= '<br/><strong>'
                  .$this->__('SKU')
                  .':</strong> '
                  .$this->getHelper('Data')->escapeHtml($tempSku);

        return $title;
    }

    public function callbackColumnGeneralId($value, $row, $column, $isExport)
    {
        $url = $this->getHelper('Component\Amazon')
            ->getItemUrl($row->getChildObject()->getData('general_id'), $row->getData('marketplace_id'));
        return '<a href="'.$url.'" target="_blank">'.$row->getChildObject()->getData('general_id').'</a>';
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('online_qty');
        if ((bool)$row->getChildObject()->getData('is_afn_channel')) {
            $sku = $row->getChildObject()->getData('sku');

            $afn = $this->__('AFN');
            $total = $this->__('Total');
            $inStock = $this->__('In Stock');
            $productId = $row->getData('id');
            $accountId = $row->getData('account_id');

            return <<<HTML
<div id="m2ePro_afn_qty_value_{$productId}">
    <span class="m2ePro-online-sku-value" productId="{$productId}" style="display: none">{$sku}</span>
    <span class="m2epro-empty-afn-qty-data" style="display: none">{$afn}</span>
    <div class="m2epro-afn-qty-data" style="display: none">
        <div class="total">{$total}: <span></span></div>
        <div class="in-stock">{$inStock}: <span></span></div>
    </div>
    <a href="javascript:void(0)"
        onclick="AmazonListingAfnQtyObj.showAfnQty(this,'{$sku}',{$productId}, {$accountId})">{$afn}</a>
</div>
HTML;
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $html ='';
        $value = $row->getChildObject()->getData('online_price');

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            (int)$row->getChildObject()->getData('is_repricing') == 1) {

            $icon = 'repricing-enabled';
            $text = $this->__(
                'This Product is used by Amazon Repricing Tool, so its Price cannot be managed via M2E Pro. <br>
                 <strong>Please note</strong> that the Price value(s) shown in the grid might
                 be different from the actual one from Amazon. It is caused by the delay
                 in the values updating made via the Repricing Service'
            );

            if ((int)$row->getChildObject()->getData('is_repricing_disabled') == 1) {
                $icon = 'repricing-disabled';
                $text = $this->__(
                    'This product is disabled on Amazon Repricing Tool. <br>
                    You can map it to Magento Product and Move into M2E Pro Listing to make the
                    Price being updated via M2E Pro.'
                );
            }

            $html = <<<HTML
&nbsp;<div class="fix-magento-tooltip {$icon}" style="float:right;">
    {$this->getTooltipHtml($text)}
</div>
HTML;
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A') . $html;
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>' . $html;
        }

        $currency = $this->amazonFactory
                        ->getCachedObjectLoaded('Marketplace',$row->getData('marketplace_id'))
                        ->getChildObject()
                        ->getDefaultCurrency();

        $priceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($value) . $html;

        if ($row->getData('is_repricing') &&
            !$row->getData('is_repricing_disabled')
        ) {
            $accountId = $row->getData('account_id');
            $sku = $row->getData('sku');

            $priceValue =<<<HTML
<a id="m2epro_repricing_price_value_{$sku}"
   class="m2epro-repricing-price-value"
   sku="{$sku}"
   account_id="{$accountId}"
   href="javascript:void(0)"
   onclick="AmazonListingProductRepricingPriceObj.showRepricingPrice()">{$priceValue}</a>
HTML;

            return $priceValue.$html;
        }

        return $this->localeCurrency->getCurrency($currency)->toCurrency($value) . $html;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $coloredStstuses = [
            \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => 'gray',
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => 'green',
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => 'red',
            \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => 'orange',
        ];

        $status = $row->getData('status');

        if ($status !== null && isset($coloredStstuses[$status])) {
            $value = '<span style="color: '.$coloredStstuses[$status].';">' . $value . '</span>';
        }

        return $value.$this->getViewLogIconHtml($row->getId());
    }

    //########################################

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

        if (isset($value['afn']) && $value['afn'] !== '') {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }
            $where .= 'is_afn_channel = ' . (int)$value['afn'];
        }

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

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            (isset($value['is_repricing']) && $value['is_repricing'] !== ''))
        {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }
            $where .= 'is_repricing = ' . (int)$value['is_repricing'];
        }

        $collection->getSelect()->where($where);
    }

    //########################################

    public function getViewLogIconHtml($listingOtherId)
    {
        $listingOtherId = (int)$listingOtherId;
        $availableActionsId = array_keys($this->getAvailableActions());

        // Get last messages
        // ---------------------------------------
        $connection = $this->resourceConnection->getConnection();

        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Listing\Other\Log')->getResource()->getMainTable(),
                array('action_id','action','type','description','create_date','initiator')
            )
            ->where('`listing_other_id` = ?', $listingOtherId)
            ->where('`action` IN (?)', $availableActionsId)
            ->order(array('id DESC'))
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        $logs = $connection->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        // ---------------------------------------

        $summary = $this->createBlock('Listing\Log\Grid\LastActions')->setData([
            'entity_id' => $listingOtherId,
            'logs'      => $logs,
            'available_actions' => $this->getAvailableActions(),
            'view_help_handler' => 'AmazonListingOtherGridObj.viewItemHelp',
            'hide_help_handler' => 'AmazonListingOtherGridObj.hideItemHelp',
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

            AmazonListingOtherGridObj.afterInitPage();
JS
            );
        }

        return parent::_beforeToHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_listing_other/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}