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
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnProductId'],
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
            'frame_callback' => [$this, 'callbackColumnWalmartSku'],
            'filter_condition_callback' => [$this, 'callbackFilterOnlineSku']
        ]);

        $this->addColumn('gtin', [
            'header' => $this->__('GTIN'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'gtin',
            'filter_index' => 'gtin',
            'frame_callback' => [$this, 'callbackColumnGtin'],
            'filter_condition_callback' => [$this, 'callbackFilterGtin']
        ]);

        $this->addColumn('online_qty', [
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => [$this, 'callbackColumnAvailableQty'],
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

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        if ($row->getData('entity_id') === null) {
            return $this->__('N/A');
        }

        $productId = (int)$row->getData('entity_id');
        $storeId = (int)$row->getData('store_id');

        $url = $this->getUrl('catalog/product/edit', ['id' => $productId]);
        $withoutImageHtml = '<a href="'.$url.'" target="_blank">'.$productId.'</a>';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')
            ->getConfig()
            ->getGroupValue('/view/', 'show_products_thumbnails');

        if (!$showProductsThumbnails) {
            return $withoutImageHtml;
        }

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);
        $magentoProduct->setStoreId($storeId);

        $imageUrlResized = $magentoProduct->getThumbnailImage();
        if ($imageUrlResized === null) {
            return $withoutImageHtml;
        }

        $imageUrlResizedUrl = $imageUrlResized->getUrl();

        $imageHtml = $productId.'<div style="margin-top: 5px;">'.
           '<img style="max-width: 100px; max-height: 100px;" src="' .$imageUrlResizedUrl. '" /></div>';
        $withImageHtml = str_replace('>'.$productId.'<', '>'.$imageHtml.'<', $withoutImageHtml);

        return $withImageHtml;
    }

    public function callbackColumnWalmartSku($value, $row, $column, $isExport)
    {
        if ($value === null || $value === '') {
            return $this->__('N/A');
        }

        return $value;
    }

    public function callbackColumnGtin($gtin, $row, $column, $isExport)
    {
        if (empty($gtin)) {
            return $this->__('N/A');
        }

        $gtinHtml = $this->getHelper('Data')->escapeHtml($gtin);
        $channelUrl = $row->getData('channel_url');

        if (!empty($channelUrl)) {
            $gtinHtml = <<<HTML
<a href="{$channelUrl}" target="_blank">{$gtin}</a>
HTML;
        }

        $html = <<<HTML
<div class="walmart-identifiers-gtin">{$gtinHtml}</div>
HTML;

        $identifiers = [
            'UPC'        => $row->getData('upc'),
            'EAN'        => $row->getData('ean'),
            'ISBN'       => $row->getData('isbn'),
            'Walmart ID' => $row->getData('wpid'),
            'Item ID'    => $row->getData('item_id')
        ];

        $htmlAdditional = '';
        foreach ($identifiers as $title => $value) {
            if (empty($value)) {
                continue;
            }

            if (($row->getData('upc') || $row->getData('ean') || $row->getData('isbn')) &&
                ($row->getData('wpid') || $row->getData('item_id')) && $title == 'Walmart ID') {
                $htmlAdditional .= "<div class='separator-line'></div>";
            }
            $identifierCode  = $this->__($title);
            $identifierValue = $this->getHelper('Data')->escapeHtml($value);

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
&nbsp;<div class="fix-magento-tooltip">
    {$this->getTooltipHtml($htmlAdditional)}
</div>
HTML;
        }

        return $html;
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if (!$row->getData('is_variation_parent')) {
            if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
            }

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

        $variationChildStatuses = $this->getHelper('Data')->jsonDecode($row->getData('variation_child_statuses'));

        if (empty($variationChildStatuses)) {
            return $this->__('N/A');
        }

        $activeChildrenCount = 0;
        foreach ($variationChildStatuses as $childStatus => $count) {
            if ($childStatus == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                continue;
            }
            $activeChildrenCount += (int)$count;
        }

        if ($activeChildrenCount == 0 || $value === null || $value === '') {
            return $this->__('N/A');
        }

        return $value;
    }

    //----------------------------------------

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

    //########################################
}
