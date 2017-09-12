<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search;

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
    )
    {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->localeCurrency = $localeCurrency;
        $this->ebayFactory = $ebayFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    /** @return array() */
    abstract protected function getProcessingLocks($row);

    //########################################

    abstract protected function callbackColumnActions($value, $row, $column, $isExport);

    //----------------------------------------

    abstract protected function callbackFilterProductId($collection, $column);
    abstract protected function callbackFilterTitle($collection, $column);
    abstract protected function callbackFilterPrice($collection, $column);
    abstract protected function callbackFilterOnlineQty($collection, $column);
    abstract protected function callbackFilterStatus($collection, $column);

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId'),
            'filter_condition_callback' => array($this, 'callbackFilterProductId')
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

        $this->addColumn('item_id', array(
            'header'    => $this->__('Item ID'),
            'align'     => 'left',
            'width'     => '100px',
            'type'      => 'text',
            'index'     => 'item_id',
            'filter_index' => 'item_id',
            'frame_callback' => array($this, 'callbackColumnEbayItemId')
        ));

        $this->addColumn('online_qty', array(
            'header'    => $this->__('Available QTY'),
            'align'     => 'right',
            'width'     => '50px',
            'type'      => 'number',
            'index'     => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => array($this, 'callbackColumnOnlineAvailableQty'),
            'filter_condition_callback' => array($this, 'callbackFilterOnlineQty')
        ));

        $this->addColumn('online_qty_sold', array(
            'header'    => $this->__('Sold QTY'),
            'align'     => 'right',
            'width'     => '50px',
            'type'      => 'number',
            'index'     => 'online_qty_sold',
            'filter_index' => 'online_qty_sold',
            'frame_callback' => array($this, 'callbackColumnOnlineQtySold')
        ));

        $this->addColumn('price', array(
            'header'    => $this->__('Price'),
            'align'     =>'right',
            'width'     => '50px',
            'type'      => 'number',
            'index'     => 'online_current_price',
            'filter_index' => 'online_current_price',
            'frame_callback' => array($this, 'callbackColumnPrice'),
            'filter_condition_callback' => array($this, 'callbackFilterPrice')
        ));

        $statusColumn = array(
            'header'       => $this->__('Status'),
            'width'        => '100px',
            'index'        => 'status',
            'filter_index' => 'status',
            'type'         => 'options',
            'sortable'     => false,
            'options'      => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED     => $this->__('Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN     => $this->__('Listed (Hidden)'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD       => $this->__('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED    => $this->__('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED   => $this->__('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED    => $this->__('Pending')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus'),
            'filter_condition_callback' => array($this, 'callbackFilterStatus')
        );

        $listingType = $this->getRequest()->getParam(
            'listing_type', \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO
        );

        if (
            $this->getHelper('View\Ebay')->isDuplicatesFilterShouldBeShown()
            && $listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO
        ) {
            $statusColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\Status';
        }

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
        $storeId   = (int)$row->getData('store_id');

        $url = $this->getUrl('catalog/product/edit', array('id' => $productId));
        $withoutImageHtml = '<a href="'.$url.'" target="_blank">'.$productId.'</a>';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')
                                                  ->getConfig()->getGroupValue('/view/','show_products_thumbnails');
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

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title       = $row->getData('name');
        $onlineTitle = $row->getData('online_title');

        !empty($onlineTitle) && $title = $onlineTitle;
        $value = '<div style="margin-bottom: 5px;">' . $this->getHelper('Data')->escapeHtml($title) . '</div>';

        $additionalHtml = $this->getColumnProductTitleAdditionalHtml($row);

        if (!empty($additionalHtml)) {
            $value .= $additionalHtml;
        }

        return $value;
    }

    public function callbackColumnEbayItemId($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        $url = $this->getUrl(
            '*/ebay_listing/gotoEbay/',
            array(
                'item_id'        => $row->getData('item_id'),
                'account_id'     => $row->getData('account_id'),
                'marketplace_id' => $row->getData('marketplace_id'),
            )
        );

        return '<a href="'. $url . '" target="_blank">'.$value.'</a>';
    }

    public function callbackColumnOnlineAvailableQty($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        if ($row->getData('status') != \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
            return '<span style="color: gray; text-decoration: line-through;">' . $value . '</span>';
        }

        return $value;
    }

    public function callbackColumnOnlineQtySold($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
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
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $onlineStartPrice   = $row->getData('online_start_price');
        $onlineCurrentPrice = $row->getData('online_current_price');

        if (is_null($onlineCurrentPrice) || $onlineCurrentPrice === '') {
            return $this->__('N/A');
        }

        if ((float)$onlineCurrentPrice <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        $currency = $row->getCurrency();

        if (strpos($currency, ',') !== false) {
            $currency = $this->ebayFactory
                ->getObjectLoaded('Marketplace',$row->getMarketplaceId())
                ->getChildObject()->getCurrency();
        }

        if (!empty($onlineStartPrice)) {

            $onlineReservePrice  = $row->getData('online_reserve_price');
            $onlineBuyItNowPrice = $row->getData('online_buyitnow_price');

            $onlineStartStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineStartPrice);

            $startPriceText = $this->__('Start Price');

            $onlineCurrentPriceHtml  = '';
            $onlineReservePriceHtml  = '';
            $onlineBuyItNowPriceHtml = '';

            if ($row->getData('online_bids') > 0) {
                $currentPriceText = $this->__('Current Price');
                $onlineCurrentStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineCurrentPrice);
                $onlineCurrentPriceHtml = '<strong>'.$currentPriceText.':</strong> '.$onlineCurrentStr.'<br/><br/>';
            }

            if ($onlineReservePrice > 0) {
                $reservePriceText = $this->__('Reserve Price');
                $onlineReserveStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineReservePrice);
                $onlineReservePriceHtml = '<strong>'.$reservePriceText.':</strong> '.$onlineReserveStr.'<br/>';
            }

            if ($onlineBuyItNowPrice > 0) {
                $buyItNowText = $this->__('Buy It Now Price');
                $onlineBuyItNowStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineBuyItNowPrice);
                $onlineBuyItNowPriceHtml = '<strong>'.$buyItNowText.':</strong> '.$onlineBuyItNowStr;
            }

            $intervalHtml = $this->getTooltipHtml(<<<HTML
<span style="color:gray;">
    {$onlineCurrentPriceHtml}
    <strong>{$startPriceText}:</strong> {$onlineStartStr}<br/>
    {$onlineReservePriceHtml}
    {$onlineBuyItNowPriceHtml}
</span>
HTML
            );

            $intervalHtml = <<<HTML
<div class="fix-magento-tooltip ebay-auction-grid-tooltip">{$intervalHtml}</div>
HTML;

            if ($onlineCurrentPrice > $onlineStartPrice) {
                $resultHtml = '<span style="color: grey; text-decoration: line-through;">'.$onlineStartStr.'</span>';
                $resultHtml .= '<br/>'.$intervalHtml.'&nbsp;'.
                    '<span class="product-price-value">'.$onlineCurrentStr.'</span>';

            } else {
                $resultHtml = $intervalHtml.'&nbsp;'.'<span class="product-price-value">'.$onlineStartStr.'</span>';
            }

            return $resultHtml;
        }

        $noticeHtml = '';
        if ($listingProductId = $row->getData('listing_product_id')) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);
            if ($listingProduct->getChildObject()->isVariationsReady()) {

                $noticeText = $this->__('The value is calculated as minimum price of all Child Products.');
                $noticeHtml = <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip" style="display: inline;">
    <a class="admin__field-tooltip-action" href="javascript://" style="margin-left: 0;"></a>
    <div class="admin__field-tooltip-content">
        {$noticeText}
    </div>
</div>
HTML;
            }
        }

        return $noticeHtml .
               '<div style="display: inline;">' .
                   '<span class="product-price-value">' .
                        $this->localeCurrency->getCurrency($currency)->toCurrency($onlineCurrentPrice) .
                   '</span>' .
               '</div>';
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        switch ($row->getData('status')) {

            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                $value = '<span style="color: gray;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $value = '<span style="color: green;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN:
                $value = '<span style="color: red;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD:
                $value = '<span style="color: brown;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $value = '<span style="color: red;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED:
                $value = '<span style="color: blue;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $value = '<span style="color: orange;">'.$value.'</span>';
                break;

            default:
                break;
        }

        $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
        if ($row->getData('is_duplicate') && isset($additionalData['item_duplicate_action_required'])) {

            $linkContent = $this->__('Duplicate');

            $value .= <<<HTML
<div class="icon-warning left">
    <span style="color: #ea7601;">{$linkContent}</span>
</div>
HTML;
        }

        /** @var \Ess\M2ePro\Model\Processing\Lock[] $processingLocks */
        $processingLocks = $this->getProcessingLocks($row);

        if (empty($processingLocks)) {
            return $value;
        }

        foreach ($processingLocks as $lock) {

            switch ($lock->getTag()) {

                case 'list_action':
                    $value .= '<br/><span style="color: #605fff">[List in Progress...]</span>';
                    break;

                case 'relist_action':
                    $value .= '<br/><span style="color: #605fff">[Relist in Progress...]</span>';
                    break;

                case 'revise_action':
                    $value .= '<br/><span style="color: #605fff">[Revise in Progress...]</span>';
                    break;

                case 'stop_action':
                    $value .= '<br/><span style="color: #605fff">[Stop in Progress...]</span>';
                    break;

                case 'stop_and_remove_action':
                    $value .= '<br/><span style="color: #605fff">[Stop And Remove in Progress...]</span>';
                    break;

                default:
                    break;
            }
        }

        return $value;
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_search/index', array('_current'=>true));
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

    //########################################
}