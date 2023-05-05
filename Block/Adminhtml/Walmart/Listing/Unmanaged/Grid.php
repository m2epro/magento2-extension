<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Unmanaged;

use Ess\M2ePro\Model\Listing\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    private $localeCurrency;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        $this->localeCurrency = $localeCurrency;
        $this->walmartFactory = $walmartFactory;
        $this->dataHelper = $dataHelper;
        $this->walmartHelper = $walmartHelper;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartListingUnmanagedGrid');

        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

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

        if ($accountId = $this->getRequest()->getParam('walmartAccount')) {
            $collection->addFieldToFilter('main_table.account_id', $accountId);
        }

        if ($marketplaceId = $this->getRequest()->getParam('walmartMarketplace')) {
            $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => __('Product ID'),
            'align' => 'left',
            'width' => '80px',
            'type' => 'number',
            'index' => 'product_id',
            'filter_index' => 'product_id',
            'frame_callback' => [$this, 'callbackColumnProductId'],
            'filter' => \Ess\M2ePro\Block\Adminhtml\Grid\Column\Filter\ProductId::class,
            'filter_condition_callback' => [$this, 'callbackFilterProductId'],
        ]);

        $this->addColumn('title', [
            'header' => __('Title / SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'filter_index' => 'second_table.title',
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('gtin', [
            'header' => __('GTIN'),
            'align' => 'left',
            'width' => '160px',
            'type' => 'text',
            'index' => 'gtin',
            'filter_index' => 'gtin',
            'frame_callback' => [$this, 'callbackColumnGtin'],
            'filter_condition_callback' => [$this, 'callbackFilterGtin'],
        ]);

        $this->addColumn('online_qty', [
            'header' => __('QTY'),
            'align' => 'right',
            'width' => '160px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => [$this, 'callbackColumnAvailableQty'],
            'filter_condition_callback' => [$this, 'callbackFilterQty'],
        ]);

        $priceColumn = [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '160px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice'],
        ];

        $this->addColumn('online_price', $priceColumn);

        $this->addColumn('status', [
            'header' => __('Status'),
            'width' => '170px',
            'index' => 'status',
            'filter_index' => 'main_table.status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                Product::STATUS_LISTED => __('Active'),
                Product::STATUS_STOPPED => __('Inactive'),
                Product::STATUS_BLOCKED => __('Incomplete'),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        $this->getMassactionBlock()->setGroups([
            'mapping' => __('Linking'),
            'other' => __('Other'),
        ]);

        $this->getMassactionBlock()->addItem('autoMapping', [
            'label' => __('Link Item(s) Automatically'),
            'url' => '',
        ], 'mapping');
        $this->getMassactionBlock()->addItem('moving', [
            'label' => __('Move Item(s) to Listing'),
            'url' => '',
        ], 'other');
        $this->getMassactionBlock()->addItem('removing', [
            'label' => __('Remove Item(s)'),
            'url' => '',
        ], 'other');
        $this->getMassactionBlock()->addItem('unmapping', [
            'label' => __('Unlink Item(s)'),
            'url' => '',
        ], 'mapping');

        return parent::_prepareMassaction();
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/other/view/grid.css');

        return parent::_prepareLayout();
    }

    public function callbackColumnProductId($value, $row, $column, $isExport): string
    {
        if (empty($value)) {
            $productTitle = $row->getChildObject()->getData('title');
            if (strlen($productTitle) > 60) {
                $productTitle = substr($productTitle, 0, 60) . '...';
            }

            $productTitle = $this->dataHelper->escapeHtml($productTitle);
            $productTitle = $this->dataHelper->escapeJs($productTitle);

            $htmlValue = '&nbsp;<a href="javascript:void(0);"
                                    onclick="ListingOtherMappingObj.openPopUp(
                                    ' . (int)$row->getId() . ',
                                    \'' . $productTitle . '\'
                                    );">' . __('Link') . '</a>';

            return $htmlValue;
        }

        $htmlValue = '&nbsp<a href="'
            . $this->getUrl(
                'catalog/product/edit',
                ['id' => $row->getData('product_id')]
            )
            . '" target="_blank">'
            . $row->getData('product_id')
            . '</a>';

        $htmlValue .= '&nbsp&nbsp&nbsp<a href="javascript:void(0);"'
            . ' onclick="WalmartListingOtherGridObj.movingHandler.getGridHtml('
            . \Ess\M2ePro\Helper\Json::encode([(int)$row->getData('id')])
            . ')">'
            . __('Move')
            . '</a>';

        return $htmlValue;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getChildObject()->getData('title');

        if ($title === null) {
            $value = '<i style="color:gray;">receiving...</i>';
        } else {
            $value = '<span>' . $this->escapeHtml($title) . '</span>';
        }

        $tempSku = $row->getChildObject()->getData('sku');
        empty($tempSku) && $tempSku = __('N/A');

        $value .= '<br/><strong>'
            . __('SKU')
            . ':</strong> '
            . $this->escapeHtml($tempSku);

        return $value;
    }

    public function callbackColumnGtin($gtin, $row, $column, $isExport)
    {
        $childObject = $row->getChildObject();
        $gtin = $childObject->getData('gtin');

        if (empty($gtin)) {
            return __('N/A');
        }

        $gtinHtml = $this->escapeHtml($gtin);

        $marketplaceId = $row->getData('marketplace_id');
        $channelUrl = $this->walmartHelper->getItemUrl(
            $childObject->getData($this->walmartHelper->getIdentifierForItemUrl($marketplaceId)),
            $marketplaceId
        );

        if (!empty($channelUrl)) {
            $gtinHtml = <<<HTML
<a href="{$channelUrl}" target="_blank">{$gtin}</a>
HTML;
        }

        $html = <<<HTML
<div class="walmart-identifiers-gtin" style="display: inline-block">{$gtinHtml}</div>
HTML;

        $identifiers = [
            'UPC' => $childObject->getData('upc'),
            'EAN' => $childObject->getData('ean'),
            'ISBN' => $childObject->getData('isbn'),
            'Walmart ID' => $childObject->getData('wpid'),
            'Item ID' => $childObject->getData('item_id'),
        ];

        $htmlAdditional = '';
        foreach ($identifiers as $title => $value) {
            if (empty($value)) {
                continue;
            }

            if (
                ($childObject->getData('upc') || $childObject->getData('ean') || $childObject->getData('isbn')) &&
                ($childObject->getData('wpid') || $childObject->getData('item_id')) && $title == 'Walmart ID'
            ) {
                $htmlAdditional .= "<div class='separator-line'></div>";
            }
            $identifierCode = __($title);
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
        if ($value === null || $value === '' || $row->getData('status') == Product::STATUS_BLOCKED) {
            return __('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('online_price');
        if ($value === null || $value === '' || $row->getData('status') == Product::STATUS_BLOCKED) {
            return __('N/A');
        }

        $currency = $this->walmartFactory
            ->getObjectLoaded('Marketplace', $row->getData('marketplace_id'))
            ->getChildObject()
            ->getDefaultCurrency();
        $value = $this->localeCurrency->getCurrency($currency)->toCurrency($value);

        if ($row->getChildObject()->getData('is_online_price_invalid')) {
            $message = <<<HTML
Item Price violates Walmart pricing rules. Please adjust the Item Price to comply with the Walmart requirements.<br>
Once the changes are applied, Walmart Item will become Active automatically.
HTML;
            $msg = '<p>' . __($message) . '</p>';
            if (empty($msg)) {
                return $value;
            }

            $value .= <<<HTML
<span class="fix-magento-tooltip">
    {$this->getTooltipHtml($message, 'map_link_defected_message_icon_' . $row->getId())}
</span>
HTML;

            return $value;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        switch ($row->getData('status')) {
            case Product::STATUS_LISTED:
                $value = '<span style="color: green;">' . $value . '</span>';
                break;

            case Product::STATUS_STOPPED:
                $value = '<span style="color: red;">' . $value . '</span>';
                break;

            case Product::STATUS_BLOCKED:
                $value = '<span style="color: orange; font-weight: bold;">' . $value . '</span>';
                break;

            default:
                break;
        }

        /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
        $listingOther = $this->walmartFactory
            ->getObjectLoaded('Listing\Other', $row->getData('id'));

        $statusChangeReasons = $listingOther->getChildObject()->getStatusChangeReasons();

        return $value . $this->getStatusChangeReasons($statusChangeReasons);
    }

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

        $collection->getSelect()->where('second_table.title LIKE ? OR second_table.sku LIKE ?', '%' . $value . '%');
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = 'main_table.status <> ' . Product::STATUS_BLOCKED;

        if (isset($value['from']) && $value['from'] != '') {
            $where .= ' AND online_qty >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            $where .= ' AND online_qty <= ' . (int)$value['to'];
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

        $where = 'main_table.status <> ' . Product::STATUS_BLOCKED;

        if (isset($value['from']) && $value['from'] != '') {
            $where .= ' AND online_price >= ' . (float)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            $where .= ' AND online_price <= ' . (float)$value['to'];
        }

        $collection->getSelect()->where($where);
    }

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

    protected function _beforeToHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getParam('isAjax')) {
            $this->js->addRequireJs(
                [
                    'jQuery' => 'jquery',
                ],
                <<<JS

            WalmartListingOtherGridObj.afterInitPage();
JS
            );
        }

        return parent::_beforeToHtml();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_listing_unmanaged/grid', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }
}
