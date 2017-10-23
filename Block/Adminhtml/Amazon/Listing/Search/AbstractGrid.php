<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search;

abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $magentoProductCollectionFactory;
    protected $localeCurrency;
    protected $amazonFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->localeCurrency = $localeCurrency;
        $this->amazonFactory = $amazonFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    abstract public function callbackColumnProductTitle($value, $row, $column, $isExport);
    abstract public function callbackColumnStatus($value, $row, $column, $isExport);
    abstract public function callbackColumnActions($value, $row, $column, $isExport);

    //----------------------------------------

    abstract protected function callbackFilterProductId($collection, $column);
    abstract protected function callbackFilterTitle($collection, $column);
    abstract protected function callbackFilterOnlineSku($collection, $column);
    abstract protected function callbackFilterPrice($collection, $column);
    abstract protected function callbackFilterQty($collection, $column);
    abstract protected function callbackFilterStatus($collection, $column);

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('name', array(
            'header'    => $this->__('Product Title / Listing / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('online_sku', array(
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'online_sku',
            'filter_index' => 'online_sku',
            'frame_callback' => array($this, 'callbackColumnAmazonSku'),
            'filter_condition_callback' => array($this, 'callbackFilterOnlineSku')
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
            'width' => '70px',
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
            'index' => 'online_current_price',
            'filter_index' => 'online_current_price',
            'frame_callback' => array($this, 'callbackColumnPrice'),
            'filter_condition_callback' => array($this, 'callbackFilterPrice')
        );

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $this->activeRecordFactory->getObject('Amazon\Account\Repricing')->getCollection()->getSize() > 0) {
            $priceColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price';
        }

        $this->addColumn('online_price', $priceColumn);

        $statusColumn = array(
            'header' => $this->__('Status'),
            'width' => '125px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => $this->__('Unknown'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
        );

        $listingType = $this->getRequest()->getParam(
            'listing_type', \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO
        );

        if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
            unset($statusColumn['options'][\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]);
        }

        $this->addColumn('status', $statusColumn);

        $this->addColumn('goto_listing_item', array(
            'header'    => $this->__('Manage'),
            'align'     => 'center',
            'width'     => '50px',
            'type'      => 'text',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnActions')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        if (is_null($row->getData('entity_id'))) {
            return $this->__('N/A');
        }

        $productId = (int)$row->getData('entity_id');
        $storeId = (int)$row->getData('store_id');

        $url = $this->getUrl('catalog/product/edit', array('id' => $productId));
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
        if (is_null($imageUrlResized)) {
            return $withoutImageHtml;
        }

        $imageUrlResizedUrl = $imageUrlResized->getUrl();

        $imageHtml = $productId.'<div style="margin-top: 5px;">'.
           '<img style="max-width: 100px; max-height: 100px;" src="' .$imageUrlResizedUrl. '" /></div>';
        $withImageHtml = str_replace('>'.$productId.'<','>'.$imageHtml.'<',$withoutImageHtml);

        return $withImageHtml;
    }

    public function callbackColumnAmazonSku($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        return $value;
    }

    public function callbackColumnGeneralId($value, $row, $column, $isExport)
    {
        if (empty($value)) {

            if ((int)$row->getData('status') != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<i style="color:gray;">'.$this->__('receiving...').'</i>';
            }

            if ($row->getData('is_general_id_owner')) {
                return $this->__('New ASIN/ISBN');
            }

            return $this->__('N/A');
        }

        $url = $this->getHelper('Component\Amazon')->getItemUrl($value, $row->getData('marketplace_id'));
        return '<a href="'.$url.'" target="_blank">'.$value.'</a>';
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if (!$row->getData('is_variation_parent')) {

            if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
            }

            if ((bool)$row->getData('is_afn_channel')) {
                $sku = $row->getData('online_sku');

                if (empty($sku)) {
                    return $this->__('AFN');
                }

                $productId = $this->getHelper('Data')->generateUniqueHash();

                $afnWord     = $this->__('AFN');
                $totalWord   = $this->__('Total');
                $inStockWord = $this->__('In Stock');
                $accountId   = $row->getData('account_id');

                return <<<HTML
<div id="m2ePro_afn_qty_value_{$productId}">
    <span class="m2ePro-online-sku-value" productId="{$productId}" style="display: none">{$sku}</span>
    <span class="m2epro-empty-afn-qty-data" style="display: none">{$afnWord}</span>
    <div class="m2epro-afn-qty-data" style="display: none">
        <div class="total">{$totalWord}: <span></span></div>
        <div class="in-stock">{$inStockWord}: <span></span></div>
    </div>
    <a href="javascript:void(0)"
        onclick="AmazonListingAfnQtyObj.showAfnQty(this,'{$sku}','{$productId}',{$accountId})">{$afnWord}</a>
</div>
HTML;
            }

            if (is_null($value) || $value === '') {
                return '<i style="color:gray;">receiving...</i>';
            }

            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        if ($row->getData('general_id') == '') {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
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

        if ($activeChildrenCount == 0) {
            return $this->__('N/A');
        }

        if (!(bool)$row->getData('is_afn_channel')) {
            return $value;
        }

        $resultValue = $this->__('AFN');
        $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

        if (!empty($additionalData['afn_count'])) {
            $resultValue = $resultValue."&nbsp;[".$additionalData['afn_count']."]";
        }

        return <<<HTML
    <div>{$value}</div>
    <div>{$resultValue}</div>
HTML;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ((!$row->getData('is_variation_parent') &&
            $row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) ||
            ($row->getData('is_variation_parent') && $row->getData('general_id') == '')) {

            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $repricingHtml = '';

        if (
            $this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $row->getData('is_repricing')
        ) {

            if ($row->getData('is_variation_parent')) {

                $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

                $enabledCount = isset($additionalData['repricing_enabled_count'])
                    ? $additionalData['repricing_enabled_count'] : null;

                $disabledCount = isset($additionalData['repricing_disabled_count'])
                    ? $additionalData['repricing_disabled_count'] : null;

                if ($enabledCount && $disabledCount) {
                    $icon = 'repricing-enabled-disabled';
                    $countHtml = '['.$enabledCount.'/'.$disabledCount.']';
                    $text = $this->__(
                        'This Parent has either Enabled and Disabled for dynamic repricing Child Products. <br>
                        <strong>Please note</strong> that the Price value(s) shown in the grid might be
                        different from the actual one from Amazon. It is caused by the delay in the values
                        updating made via the Repricing Service.'
                    );
                } elseif ($enabledCount) {
                    $icon = 'repricing-enabled';
                    $countHtml = '['.$enabledCount.']';
                    $text = $this->__(
                        'All Child Products of this Parent are Enabled for dynamic repricing. <br>
                        <strong>Please note</strong> that the Price value(s) shown in the grid might be different
                        from the actual one from Amazon. It is caused by the delay in the values updating
                        made via the Repricing Service.'
                    );
                } elseif ($disabledCount) {
                    $icon = 'repricing-disabled';
                    $countHtml = '['.$disabledCount.']';
                    $text = $this->__('All Child Products of this Parent are Disabled for Repricing.');
                } else {
                    $icon = 'repricing-enabled';
                    $countHtml = $this->__('[-/-]');
                    $text = $this->__(
                        'Some Child Products of this Parent are managed by the Repricing Service. <br>
                        <strong>Please note</strong> that the Price value(s) shown in the grid might be
                        different from the actual one from Amazon. It is caused by the delay in the
                        values updating made via the Repricing Service.'
                    );
                }

                $repricingHtml = <<<HTML
<br/>
<div class="fix-magento-tooltip {$icon}">
    {$this->getTooltipHtml($text)}
</div>
    &nbsp;$countHtml&nbsp;
HTML;
            } elseif (!$row->getData('is_variation_parent')) {
                $icon = 'repricing-enabled';
                $text = $this->__(
                    'This Product is used by Amazon Repricing Tool, so its Price cannot be managed via M2E Pro.<br>
                    <strong>Please note</strong> that the Price value shown in the grid might be different
                    from the actual one from Amazon. It is caused by the delay in the values
                    updating made via the Repricing Service.'
                );

                if ((int)$row->getData('is_repricing_disabled') == 1) {
                    $icon = 'repricing-disabled';

                    if ($this->getId() == 'amazonListingSearchOtherGrid') {
                        $text = $this->__(
                            'This product is disabled on Amazon Repricing Tool. <br>
                            You can map it to Magento Product and Move into M2E Pro Listing to make the
                            Price being updated via M2E Pro.'
                        );
                    } else {
                        $text = $this->__(
                            'This product is disabled on Amazon Repricing Tool.
                            The Price is updated through the M2E Pro.'
                        );
                    }

                }

                $repricingHtml = <<<HTML
&nbsp;<div class="fix-magento-tooltip {$icon}">
    {$this->getTooltipHtml($text)}
</div>
HTML;
            }
        }

        $currentOnlinePrice = (float)$row->getData('online_current_price');
        $onlineBusinessPrice = (float)$row->getData('online_business_price');

        if ((is_null($currentOnlinePrice) || $currentOnlinePrice === '') && empty($onlineBusinessPrice)) {
            if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED ||
                $row->getData('is_variation_parent')
            ) {
                return $this->__('N/A') . $repricingHtml;
            } else {
                return '<i style="color:gray;">receiving...</i>' . $repricingHtml;
            }
        }

        $marketplaceId = $row->getData('marketplace_id');
        $currency = $this->amazonFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getChildObject()
            ->getDefaultCurrency();

        if ($row->getData('is_variation_parent')) {
            $noticeText = $this->__('The value is calculated as minimum price of all Child Products.');

            $priceHtml = <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip" style="display: inline;">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$noticeText}
    </div>
</div>
HTML;

            if (!empty($currentOnlinePrice)) {
                $currentOnlinePrice = $this->localeCurrency->getCurrency($currency)->toCurrency($currentOnlinePrice);
                $priceHtml .= "<span>{$currentOnlinePrice}</span><br />";
            }

            if (!empty($onlineBusinessPrice)) {
                $priceHtml .= '<strong>B2B:</strong> '
                              .$this->localeCurrency->getCurrency($currency)->toCurrency($onlineBusinessPrice);
            }

            return $priceHtml . $repricingHtml;
        }

        $onlinePrice = $row->getData('online_regular_price');
        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($onlinePrice);
        }

        if ($row->getData('is_repricing') &&
            !$row->getData('is_repricing_disabled') &&
            !$row->getData('is_variation_parent')
        ) {
            $accountId = $row->getData('account_id');
            $sku = $row->getData('online_sku');

            $priceValue =<<<HTML
<a id="m2epro_repricing_price_value_{$sku}"
   class="m2epro-repricing-price-value"
   sku="{$sku}"
   account_id="{$accountId}"
   href="javascript:void(0)"
   onclick="AmazonListingProductRepricingPriceObj.showRepricingPrice()">{$priceValue}</a>
HTML;
        }

        $resultHtml = '';

        $salePrice = $row->getData('online_regular_sale_price');
        if (!$row->getData('is_variation_parent') && (float)$salePrice > 0 && !$row->getData('is_repricing')) {
            $currentTimestamp = strtotime($this->getHelper('Data')->getCurrentGmtDate(false,'Y-m-d 00:00:00'));

            $startDateTimestamp = strtotime($row->getData('online_regular_sale_price_start_date'));
            $endDateTimestamp   = strtotime($row->getData('online_regular_sale_price_end_date'));

            if ($currentTimestamp <= $endDateTimestamp) {
                $fromDate = $this->_localeDate->formatDate(
                    $row->getData('online_regular_sale_price_start_date'), \IntlDateFormatter::MEDIUM
                );
                $toDate = $this->_localeDate->formatDate(
                    $row->getData('online_regular_sale_price_end_date'), \IntlDateFormatter::MEDIUM
                );

                $intervalHtml = <<<HTML
<div class="m2epro-field-tooltip m2epro-field-tooltip-price-info admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        <span style="color:gray;">
            <strong>From:</strong> {$fromDate}<br/>
            <strong>To:</strong> {$toDate}
        </span>
    </div>
</div>
HTML;

                $salePriceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($salePrice);

                if ($currentTimestamp >= $startDateTimestamp &&
                    $currentTimestamp <= $endDateTimestamp &&
                    $salePrice < (float)$onlinePrice
                ) {
                    $resultHtml .= '<span style="color: grey; text-decoration: line-through;">'.$priceValue.'</span>' .
                                    $repricingHtml;
                    $resultHtml .= '<br/>'.$intervalHtml.'&nbsp;'.$salePriceValue;
                } else {
                    $resultHtml .= $priceValue . $repricingHtml;
                    $resultHtml .= '<br/>'.$intervalHtml.
                        '<span style="color:gray;">'.'&nbsp;'.$salePriceValue.'</span>';
                }
            }
        }

        if (empty($resultHtml)) {
            $resultHtml = $priceValue . $repricingHtml;
        }

        if ((float)$onlineBusinessPrice > 0) {
            $businessPriceValue = '<strong>B2B:</strong> '
                                  .$this->localeCurrency->getCurrency($currency)->toCurrency($onlineBusinessPrice);

            $businessDiscounts = $row->getData('online_business_discounts');
            if (!empty($businessDiscounts) && $businessDiscounts = json_decode($businessDiscounts, true)) {
                $discountsHtml = '';

                foreach ($businessDiscounts as $qty => $price) {
                    $price = $this->localeCurrency->getCurrency($currency)->toCurrency($price);
                    $discountsHtml .= 'QTY >= '.(int)$qty.', price '.$price.'<br />';
                }

                $businessPriceValue .= $this->getTooltipHtml($discountsHtml);
            }

            if (!empty($resultHtml)) {
                $businessPriceValue = '<br />'.$businessPriceValue;
            }

            $resultHtml .= $businessPriceValue;
        }

        return $resultHtml;
    }

    //----------------------------------------

    protected function getProductStatus($status)
    {
        switch ($status) {

            case \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN:
                return '<span style="color: gray;">' . $this->__('Unknown') . '</span>';

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

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_listing_search/index', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->addUrls([
            'amazon_listing/getAFNQtyBySku' => $this->getUrl('*/amazon_listing/getAFNQtyBySku'),
            'amazon_listing_product_repricing/getUpdatedPriceBySkus' => $this->getUrl(
                '*/amazon_listing_product_repricing/getUpdatedPriceBySkus'
            )
        ]);

        $this->js->addRequireJs([
            'alq' => 'M2ePro/Amazon/Listing/AfnQty',
            'alprp' => 'M2ePro/Amazon/Listing/Product/Repricing/Price'
        ], <<<JS
        window.AmazonListingAfnQtyObj = new AmazonListingAfnQty();
        window.AmazonListingProductRepricingPriceObj = new AmazonListingProductRepricingPrice();
JS
        );

        return parent::_toHtml();
    }

    //########################################
}