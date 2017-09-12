<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Step\Products;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $localeCurrency;
    protected $ebayFactory;
    protected $magentoProductCollectionFactory;
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->localeCurrency = $localeCurrency;
        $this->ebayFactory = $ebayFactory;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('temp_data');

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingPickupStoreStepProducts');
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ?
                $column->getFilterIndex() : $column->getIndex();
            $collection->getSelect()->order($columnIndex.' '.strtoupper($column->getDir()));
        }
        return $this;
    }

    //########################################

    protected function _prepareCollection()
    {
        // ---------------------------------------
        // Get collection
        // ---------------------------------------
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setListingProductModeOn();
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');
        // ---------------------------------------

        // Join listing product tables
        // ---------------------------------------
        $collection->joinTable(
            ['lp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()],
            'product_id=entity_id',
            [
                'id' => 'id',
                'ebay_status' => 'status',
                'component_mode' => 'component_mode',
                'additional_data' => 'additional_data'
            ],
            '{{table}}.listing_id='.(int)$this->listing->getData('id')
        );
        $collection->joinTable(
            ['elp' => $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable()],
            'listing_product_id=id',
            [
                'listing_product_id'    => 'listing_product_id',
                'end_date'              => 'end_date',
                'start_date'            => 'start_date',
                'online_title'          => 'online_title',
                'online_sku'            => 'online_sku',
                'available_qty'         => new \Zend_Db_Expr('(elp.online_qty - elp.online_qty_sold)'),
                'ebay_item_id'          => 'ebay_item_id',
                'online_category'       => 'online_category',
                'online_qty_sold'       => 'online_qty_sold',
                'online_bids'           => 'online_bids',
                'online_start_price'    => 'online_start_price',
                'online_current_price'  => 'online_current_price',
                'online_reserve_price'  => 'online_reserve_price',
                'online_buyitnow_price' => 'online_buyitnow_price',
                'template_category_id'  => 'template_category_id',
                'min_online_price'      => 'IF(
                    (`t`.`variation_min_price` IS NULL),
                    `elp`.`online_current_price`,
                    `t`.`variation_min_price`
                )',
                'max_online_price'      => 'IF(
                    (`t`.`variation_max_price` IS NULL),
                    `elp`.`online_current_price`,
                    `t`.`variation_max_price`
                )'
            ]
        );
        $collection->joinTable(
            ['ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
            'id=ebay_item_id',
            ['item_id' => 'item_id'],
            NULL,
            'left'
        );
        $collection->getSelect()->joinLeft(
            new \Zend_Db_Expr('(
                SELECT
                    `mlpv`.`listing_product_id`,
                    MIN(`melpv`.`online_price`) as variation_min_price,
                    MAX(`melpv`.`online_price`) as variation_max_price
                FROM `'. $this->activeRecordFactory->getObject('Listing\Product\Variation')
                              ->getResource()->getMainTable() .'` AS `mlpv`
                INNER JOIN `' .
                $this->activeRecordFactory->getObject('Ebay\Listing\Product\Variation')->getResource()->getMainTable() .
                '` AS `melpv`
                    ON (`mlpv`.`id` = `melpv`.`listing_product_variation_id`)
                WHERE `melpv`.`status` != ' . \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED . '
                GROUP BY `mlpv`.`listing_product_id`
            )'),
            'elp.listing_product_id=t.listing_product_id',
            [
                'variation_min_price' => 'variation_min_price',
                'variation_max_price' => 'variation_max_price',
            ]
        );
        // ---------------------------------------

        if ($this->listing) {
            $collection->setStoreId($this->listing['store_id']);
        }

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Set massaction identifiers
        // ---------------------------------------
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        // Set fake action
        // ---------------------------------------
        if ($this->getMassactionBlock()->getCount() == 0) {
            $this->getMassactionBlock()->addItem('fake', array(
                'label' => '&nbsp;&nbsp;&nbsp;&nbsp;',
                'url'   => '#',
            ));
            // Header of grid with massactions is rendering in other way, than with no massaction
            // so it causes broken layout when the actions are absent
            $this->css->add(<<<CSS
            #{$this->getId()} .admin__data-grid-header {
                display: -webkit-flex;
                display: flex;
                -webkit-flex-wrap: wrap;
                flex-wrap: wrap;
            }

            #{$this->getId()} > .admin__data-grid-header > .admin__data-grid-header-row:first-child {
                width: 20%;
                margin-top: 1.1em;
            }
            #{$this->getId()} > .admin__data-grid-header > .admin__data-grid-header-row:last-child {
                width: 79%;
            }
CSS
            );
        }
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnListingProductId'],
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'online_title',
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('ebay_item_id', [
            'header'    => $this->__('Item ID'),
            'align'     => 'left',
            'width'     => '100px',
            'type'      => 'text',
            'index'     => 'item_id',
            'frame_callback' => [$this, 'callbackColumnEbayItemId']
        ]);

        $this->addColumn('available_qty', [
            'header'    => $this->__('Available QTY'),
            'align'     => 'right',
            'width'     => '50px',
            'type'      => 'number',
            'index'     => 'available_qty',
            'filter'    => false,
            'frame_callback' => [$this, 'callbackColumnOnlineAvailableQty']
        ]);

        $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
        if ($dir == 'desc') {
            $priceSortField = 'max_online_price';
        } else {
            $priceSortField = 'min_online_price';
        }

        $this->addColumn('price', [
            'header'    => $this->__('Price'),
            'align'     =>'right',
            'width'     => '75px',
            'type'      => 'number',
            'index'     => $priceSortField,
            'filter_index' => $priceSortField,
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ]);

        $this->addColumn('ebay_status', [
            'header'=> $this->__('Status'),
            'width' => '80px',
            'index' => 'ebay_status',
            'filter_index' => 'ebay_status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED     => $this->__('Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN    => $this->__('Listed (Hidden)'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD       => $this->__('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED    => $this->__('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED   => $this->__('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED    => $this->__('Pending')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnListingProductId($value, $row, $column, $isExport)
    {
        $productId = (int)$value;

        $url = $this->getUrl('catalog/product/edit', ['id' => $productId]);
        $htmlWithoutThumbnail = '<a href="' . $url . '" target="_blank">'.$productId.'</a>';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')->getConfig()
            ->getGroupValue('/view/','show_products_thumbnails');

        if (!$showProductsThumbnails) {
            return $htmlWithoutThumbnail;
        }

        $storeId = (int)$this->listing['store_id'];

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);
        $magentoProduct->setStoreId($storeId);

        $thumbnail = $magentoProduct->getThumbnailImage();
        if (is_null($thumbnail)) {
            return $htmlWithoutThumbnail;
        }

        $thumbnailUrl = $thumbnail->getUrl();

        return <<<HTML
<a href="{$url}" target="_blank">
    {$productId}
    <div style="margin-top: 5px"><img src="{$thumbnailUrl}" /></div>
</a>
HTML;
    }

    public function callbackColumnEbayItemId($value, $row, $column, $isExport)
    {
        if ($row->getData('ebay_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        $listingData = $this->listing->getData();

        $url = $this->getUrl(
            '*/ebay_listing/gotoEbay/',
            [
                'item_id' => $value,
                'account_id' => $listingData['account_id'],
                'marketplace_id' => $listingData['marketplace_id']
            ]
        );

        return '<a href="' . $url . '" target="_blank">'.$value.'</a>';
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $title = $row->getName();
        $onlineTitle = $row->getData('online_title');
        !empty($onlineTitle) && $title = $onlineTitle;

        $title = $this->getHelper('Data')->escapeHtml($title);
        $valueHtml = '<span class="product-title-value">' . $title . '</span>';

        if (is_null($sku = $row->getData('sku'))) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                                      ->setProductId($row->getData('entity_id'))->getSku();
        }

        $onlineSku = $row->getData('online_sku');
        !empty($onlineSku) && $sku = $onlineSku;

        $valueHtml .= '<br/>' .
            '<strong>' . $this->__('SKU') . ':</strong>&nbsp;' .
            $this->getHelper('Data')->escapeHtml($sku);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product',$row->getData('listing_product_id'));

        if (!$listingProduct->getChildObject()->isVariationsReady()) {
            return $valueHtml;
        }

        $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
        $productAttributes = array_keys($additionalData['variations_sets']);
        $valueHtml .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">';
        $valueHtml .= implode(', ', $productAttributes);
        $valueHtml .= '</div>';

        return $valueHtml;
    }

    public function callbackColumnOnlineAvailableQty($value, $row, $column, $isExport)
    {
        if ($row->getData('ebay_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        if ($row->getData('ebay_status') != \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
            return '<span style="color: gray; text-decoration: line-through;">' . $value . '</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($row->getData('ebay_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $onlineMinPrice = $row->getData('min_online_price');
        $onlineMaxPrice = $row->getData('max_online_price');
        $onlineStartPrice = $row->getData('online_start_price');
        $onlineCurrentPrice = $row->getData('online_current_price');

        if (is_null($onlineMinPrice) || $onlineMinPrice === '') {
            return $this->__('N/A');
        }

        if ((float)$onlineMinPrice <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        $currency = $this->listing->getMarketplace()->getChildObject()->getCurrency();

        if (!empty($onlineStartPrice)) {

            $onlineReservePrice = $row->getData('online_reserve_price');
            $onlineBuyItNowPrice = $row->getData('online_buyitnow_price');

            $onlineStartStr = $this->convertAndFormatPriceCurrency($onlineStartPrice, $currency);

            $startPriceText = $this->__('Start Price');
            $onlineCurrentPriceHtml = '';
            $onlineReservePriceHtml = '';
            $onlineBuyItNowPriceHtml = '';

            if ($row->getData('online_bids') > 0) {
                $currentPriceText = $this->__('Current Price');
                $onlineCurrentStr = $this->convertAndFormatPriceCurrency($onlineCurrentPrice, $currency);
                $onlineCurrentPriceHtml = '<strong>'.$currentPriceText.':</strong> '.$onlineCurrentStr.'<br/><br/>';
            }

            if ($onlineReservePrice > 0) {
                $reservePriceText = $this->__('Reserve Price');
                $onlineReserveStr = $this->convertAndFormatPriceCurrency($onlineReservePrice, $currency);
                $onlineReservePriceHtml = '<strong>'.$reservePriceText.':</strong> '.$onlineReserveStr.'<br/>';
            }

            if ($onlineBuyItNowPrice > 0) {
                $buyItNowText = $this->__('Buy It Now Price');
                $onlineBuyItNowStr = $this->convertAndFormatPriceCurrency($onlineBuyItNowPrice, $currency);
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

        } else {
            $onlineMinPriceStr = $this->convertAndFormatPriceCurrency($onlineMinPrice, $currency);
            $onlineMaxPriceStr = $this->convertAndFormatPriceCurrency($onlineMaxPrice, $currency);

            $resultHtml = '<span class="product-price-value">' . $onlineMinPriceStr . '</span>' .
                (($onlineMinPrice != $onlineMaxPrice) ? ' - ' . $onlineMaxPriceStr :  '');
        }

        return $resultHtml;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $html = '';

        switch ((int)$row->getData('ebay_status')) {

            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                $html = '<span style="color: gray;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $html = '<span style="color: green;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN:
                $html = '<span style="color: red;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD:
                $html = '<span style="color: brown;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $html = '<span style="color: red;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED:
                $html = '<span style="color: blue;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $html = '<span style="color: orange;">' . $value . '</span>';
                break;

            default:
                break;
        }

        return $html;
    }

    // ---------------------------------------

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute'=>'sku','like'=>'%'.$value.'%'],
                ['attribute'=>'online_sku','like'=>'%'.$value.'%'],
                ['attribute'=>'name', 'like'=>'%'.$value.'%'],
                ['attribute'=>'online_title','like'=>'%'.$value.'%'],
            ]
        );
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $condition = '';

        if (isset($value['from']) && $value['from'] != '') {
            $condition = 'min_online_price >= \''.$value['from'].'\'';
        }
        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $condition .= ' AND ';
            }
            $condition .= 'min_online_price <= \''.(float)$value['to'].'\'';
        }

        $condition = '(' . $condition . ') OR (';

        if (isset($value['from']) && $value['from'] != '') {
            $condition .= 'max_online_price >= \''.$value['from'].'\'';
        }
        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $condition .= ' AND ';
            }
            $condition .= 'max_online_price <= \''.(float)$value['to'].'\'';
        }

        $condition .= ')';

        $collection->getSelect()->having($condition);
    }

    //########################################

    protected function _toHtml()
    {
        $this->css->add(<<<CSS
            #{$this->getHtmlId()}_massaction .admin__grid-massaction-form {
                display: none;
            }
            #{$this->getHtmlId()}_massaction .mass-select-wrap {
                margin-left: -24%;
            }
CSS
        );

        $this->js->addOnReadyJs(
            <<<JS
            require([
                'jquery',
                'M2ePro/Magento/Product/Grid',
                'M2ePro/Ebay/Listing/PickupStore/Step/Products/Grid'
            ], function(jQuery){

                window.PickupStoreProductGridObj = new MagentoProductGrid();
                PickupStoreProductGridObj.setGridId('{$this->getJsObjectName()}');
                PickupStoreProductGridObj.isMassActionExists = false;

                window.EbayListingPickupStoreStepProductsGridObj = new EbayListingPickupStoreStepProductsGrid();
                EbayListingPickupStoreStepProductsGridObj.gridId = '{$this->getId()}';

                jQuery(function() {
                    {$this->getJsObjectName()}.doFilter = PickupStoreProductGridObj.setFilter;
                    {$this->getJsObjectName()}.resetFilter = PickupStoreProductGridObj.resetFilter;
                });
            });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_pickupStore/productsStepGrid', [
            'id'=>$this->listing->getId()
        ]);
    }

    //########################################

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    //########################################
}