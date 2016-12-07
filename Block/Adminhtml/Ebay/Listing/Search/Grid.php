<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search;

use \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $localeCurrency;
    protected $customCollectionFactory;
    protected $ebayFactory;
    protected $resourceConnection;
    protected $resourceStockItem;
    protected $resourceCatalogProduct;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\Item $resourceStockItem,
        \Magento\Catalog\Model\ResourceModel\Product $resourceCatalogProduct,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->localeCurrency = $localeCurrency;
        $this->customCollectionFactory = $customCollectionFactory;
        $this->ebayFactory = $ebayFactory;
        $this->resourceConnection = $resourceConnection;
        $this->resourceStockItem = $resourceStockItem;
        $this->resourceCatalogProduct = $resourceCatalogProduct;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingSearchGrid');
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
        // Get collection products in listing
        // ---------------------------------------
        $nameAttribute = $this->resourceCatalogProduct->getAttribute('name');

        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->getSelect()->distinct();

        // Communicate with magento product table
        // ---------------------------------------
        $dbSelect = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity_varchar'),
                new \Zend_Db_Expr('MAX(`store_id`)'))
            ->where("`entity_id` = `main_table`.`product_id`")
            ->where("`attribute_id` = `ea`.`attribute_id`")
            ->where("`store_id` = 0 OR `store_id` = `l`.`store_id`");

        $listingProductCollection->getSelect()->join(
            array('l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
            '`l`.`id` = `main_table`.`listing_id`'
        );

        $listingProductCollection->getSelect()->join(
            array('em' => $this->activeRecordFactory->getObject('Ebay\Marketplace')->getResource()->getMainTable()),
            '`em`.`marketplace_id` = `l`.`marketplace_id`'
        );

        $listingProductCollection->getSelect()->join(
            array('cpe' => $this->resourceConnection->getTableName('catalog_product_entity')),
            'cpe.entity_id = `main_table`.product_id',
            array('magento_sku'=>'sku')
        );

        $listingProductCollection->getSelect()->joinLeft(
            array('cpev' => $this->resourceConnection->getTableName('catalog_product_entity_varchar')),
            "(`cpev`.`entity_id` = `main_table`.product_id)",
            array('value')
        );

        $listingProductCollection->getSelect()->joinLeft(
            array('ebit' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()),
            '(`ebit`.`id` = `second_table`.`ebay_item_id`)',
            array('item_id')
        );

        $listingProductCollection->getSelect()->join(
            array('ea'=> $this->resourceConnection->getTableName('eav_attribute')),
            '(`cpev`.`attribute_id` = `ea`.`attribute_id` AND `ea`.`attribute_code` = \'name\')',
            array()
        );

        $listingProductCollection->getSelect()->where('`cpev`.`store_id` = ('.$dbSelect->__toString().')');
        // ---------------------------------------

        // add stock availability, status & visibility to select
        // ---------------------------------------
        $listingProductCollection->getSelect()->joinLeft(
            array('cisi' => $this->resourceStockItem->getMainTable()),
            '(`cisi`.`product_id` = `main_table`.`product_id` AND `cisi`.`stock_id` = 1)',
            array('is_in_stock')
        );
        // ---------------------------------------

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            array(
                'account_id'            => 'l.account_id',
                'store_id'              => 'l.store_id',
                'marketplace_id'        => 'l.marketplace_id',
                'product_id'            => 'main_table.product_id',
                'product_name'          => 'cpev.value',
                'product_sku'           => 'cpe.sku',
                'currency'              => 'em.currency',
                'ebay_item_id'          => 'ebit.item_id',
                'listing_product_id'    => 'main_table.id',
                'listing_other_id'      => new \Zend_Db_Expr('NULL'),
                'additional_data'       => 'main_table.additional_data',
                'status'                => 'main_table.status',
                'online_sku'            => 'second_table.online_sku',
                'online_title'          => 'second_table.online_title',
                'online_qty'            => new \Zend_Db_Expr(
                    '(second_table.online_qty - second_table.online_qty_sold)'
                ),
                'online_qty_sold'       => 'second_table.online_qty_sold',
                'online_bids'           => 'second_table.online_bids',
                'online_start_price'    => 'second_table.online_start_price',
                'online_current_price'  => 'second_table.online_current_price',
                'online_reserve_price'  => 'second_table.online_reserve_price',
                'online_buyitnow_price' => 'second_table.online_buyitnow_price',
                'min_online_price'      => 'IF(
                    (`t`.`variation_min_price` IS NULL),
                    `second_table`.`online_current_price`,
                    `t`.`variation_min_price`
                )',
                'max_online_price'      => 'IF(
                    (`t`.`variation_max_price` IS NULL),
                    `second_table`.`online_current_price`,
                    `t`.`variation_max_price`
                )',
                'listing_id'            => 'l.id',
                'listing_title'         => 'l.title',
                'is_m2epro_listing'     => new \Zend_Db_Expr(1),
                'is_in_stock'           => 'cisi.is_in_stock',
            )
        );
        $listingProductCollection->getSelect()->joinLeft(
            new \Zend_Db_Expr('(
                SELECT
                    `mlpv`.`listing_product_id`,
                    MIN(`melpv`.`online_price`) as variation_min_price,
                    MAX(`melpv`.`online_price`) as variation_max_price
                FROM `'. $this->activeRecordFactory->getObject('Listing\Product\Variation')
                             ->getResource()->getMainTable() .'` AS `mlpv`
                INNER JOIN `' .
                        $this->activeRecordFactory->getObject('Ebay\Listing\Product\Variation')
                             ->getResource()->getMainTable() .
                    '` AS `melpv`
                    ON (`mlpv`.`id` = `melpv`.`listing_product_variation_id`)
                WHERE `melpv`.`status` != ' . \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED . '
                GROUP BY `mlpv`.`listing_product_id`
            )'),
            'second_table.listing_product_id=t.listing_product_id',
            array(
                'variation_min_price' => 'variation_min_price',
                'variation_max_price' => 'variation_max_price',
            )
        );
        // ---------------------------------------

        // ---------------------------------------
        $listingOtherCollection = $this->ebayFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->getSelect()->distinct();

        // add stock availability, type id, status & visibility to select
        // ---------------------------------------
        $listingOtherCollection->getSelect()->joinLeft(
            array('cisi' => $this->resourceStockItem->getMainTable()),
            '(`cisi`.`product_id` = `main_table`.`product_id` AND cisi.stock_id = 1)',
            array('is_in_stock')
        );
        // ---------------------------------------

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            array(
                'account_id'            => 'main_table.account_id',
                'store_id'              => new \Zend_Db_Expr(0),
                'marketplace_id'        => 'main_table.marketplace_id',
                'product_id'            => 'main_table.product_id',
                'product_name'          => 'second_table.title',
                'product_sku'           => 'second_table.sku',
                'currency'              => 'second_table.currency',
                'ebay_item_id'          => 'second_table.item_id',
                'listing_product_id'    => new \Zend_Db_Expr('NULL'),
                'listing_other_id'      => 'main_table.id',
                'additional_data'       => new \Zend_Db_Expr('NULL'),
                'status'                => 'main_table.status',
                'online_sku'            => new \Zend_Db_Expr('NULL'),
                'online_title'          => new \Zend_Db_Expr('NULL'),
                'online_qty'            => new \Zend_Db_Expr(
                    '(second_table.online_qty - second_table.online_qty_sold)'
                ),
                'online_qty_sold'       => 'second_table.online_qty_sold',
                'online_bids'           => new \Zend_Db_Expr('NULL'),
                'online_start_price'    => new \Zend_Db_Expr('NULL'),
                'online_current_price'  => 'second_table.online_price',
                'online_reserve_price'  => new \Zend_Db_Expr('NULL'),
                'online_buyitnow_price' => new \Zend_Db_Expr('NULL'),
                'min_online_price'      => 'second_table.online_price',
                'max_online_price'      => 'second_table.online_price',
                'listing_id'            => new \Zend_Db_Expr('NULL'),
                'listing_title'         => new \Zend_Db_Expr('NULL'),
                'is_m2epro_listing'     => new \Zend_Db_Expr(0),
                'is_in_stock'           => 'cisi.is_in_stock',
                'variation_min_price'   => new \Zend_Db_Expr('NULL'),
                'variation_max_price'   => new \Zend_Db_Expr('NULL'),
            )
        );
        // ---------------------------------------

        // ---------------------------------------
        $selects = array($listingProductCollection->getSelect());
        $selects[] = $listingOtherCollection->getSelect();

        $unionSelect = $this->resourceConnection->getConnection()->select();
        $unionSelect->union($selects);

        $resultCollection = $this->customCollectionFactory->create();
        $resultCollection->setConnection($this->resourceConnection->getConnection());
        $resultCollection->getSelect()->reset()->from(
            array('main_table' => $unionSelect),
            array(
                'account_id',
                'store_id',
                'marketplace_id',
                'product_id',
                'product_name',
                'product_sku',
                'currency',
                'ebay_item_id',
                'listing_product_id',
                'listing_other_id',
                'additional_data',
                'status',
                'online_sku',
                'online_title',
                'online_qty',
                'online_qty_sold',
                'online_bids',
                'online_start_price',
                'online_current_price',
                'online_reserve_price',
                'online_buyitnow_price',
                'min_online_price',
                'max_online_price',
                'listing_id',
                'listing_title',
                'is_m2epro_listing',
                'is_in_stock'
            )
        );
        // ---------------------------------------

        $accountId = (int)$this->getRequest()->getParam('ebayAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('ebayMarketplace', false);
        $listingType = $this->getRequest()->getParam('listing_type', false);

        if ($accountId) {
            $resultCollection->getSelect()->where('account_id = ?', $accountId);
        }

        if ($marketplaceId) {
            $resultCollection->getSelect()->where('marketplace_id = ?', $marketplaceId);
        }

        if ($listingType) {

            if ($listingType == TypeSwitcher::LISTING_TYPE_M2E_PRO) {

                $resultCollection->getSelect()->where('is_m2epro_listing = ?', 1);

            } elseif ($listingType == TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {

                $resultCollection->getSelect()->where('is_m2epro_listing = ?', 0);
            }
        }

        $this->setCollection($resultCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'product_id',
            'filter_index' => 'main_table.product_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('product_name', array(
            'header'    => $this->__('Product Title / Listing / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'product_name',
            'filter_index' => 'product_name',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('ebay_item_id', array(
            'header'    => $this->__('Item ID'),
            'align'     => 'left',
            'width'     => '100px',
            'type'      => 'text',
            'index'     => 'ebay_item_id',
            'filter_index' => 'ebay_item_id',
            'frame_callback' => array($this, 'callbackColumnEbayItemId')
        ));

        $this->addColumn('online_qty', array(
            'header'    => $this->__('Available QTY'),
            'align'     => 'right',
            'width'     => '50px',
            'type'      => 'number',
            'index'     => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => array($this, 'callbackColumnOnlineAvailableQty')
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

        $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);

        if ($dir == 'desc') {
            $priceSortField = 'max_online_price';
        } else {
            $priceSortField = 'min_online_price';
        }

        $this->addColumn('price', array(
            'header'    => $this->__('Price'),
            'align'     =>'right',
            'width'     => '50px',
            'type'      => 'number',
            'index'     => $priceSortField,
            'filter_index' => $priceSortField,
            'frame_callback' => array($this, 'callbackColumnPrice'),
            'filter_condition_callback' => array($this, 'callbackFilterPrice')
        ));

        $this->addColumn('status',
            array(
                'header'=> $this->__('Status'),
                'width' => '100px',
                'index' => 'status',
                'filter_index' => 'status',
                'type'  => 'options',
                'sortable'  => false,
                'options' => array(
                    \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                    \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED     => $this->__('Listed'),
                    \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN     => $this->__('Listed (Hidden)'),
                    \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD       => $this->__('Sold'),
                    \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED    => $this->__('Stopped'),
                    \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED   => $this->__('Finished'),
                    \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED    => $this->__('Pending')
                ),
                'frame_callback' => array($this, 'callbackColumnStatus')
        ));

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
        if (is_null($row->getData('product_id'))) {
            return $this->__('N/A');
        }

        $productId = (int)$row->getData('product_id');
        $storeId = (int)$row->getData('store_id');

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

        $imageHtml = $productId.'<div style="margin-top: 5px;"><img src="'.$imageUrlResizedUrl.'" /></div>';
        $withImageHtml = str_replace('>'.$productId.'<','>'.$imageHtml.'<',$withoutImageHtml);

        return $withImageHtml;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $listingProductId = $row->getData('listing_product_id');
        $onlineTitle = $row->getData('online_title');
        !empty($onlineTitle) && $value = $onlineTitle;

        $value = '<span>' . $this->getHelper('Data')->escapeHtml($value) . '</span>';

        $additional = $this->getListingHtml($row);
        $additional .= $this->getSkuHtml($row);

        if (!empty($listingProductId)) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->ebayFactory
                ->getObjectLoaded('Listing\Product',$row->getData('listing_product_id'));

            if ($listingProduct->getChildObject()->isVariationsReady()) {
                $additionalData = (array)json_decode($row->getData('additional_data'), true);

                $productAttributes = array_keys($additionalData['variations_sets']);

                $additional .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">';
                $additional .= implode(', ', $productAttributes);
                $additional .= '</div>';
            }
        }

        if ($additional) {
            $value .= '<div style="margin-top: 5px">' . $additional . '</div>';
        }

        return $value;
    }

    private function getListingHtml($row)
    {
        $account = $this->ebayFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $accountAndMarketplaceInfo =
            '<strong>' . $this->__('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            .'<strong>' . $this->__('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle() . '<br/>';

        if (is_null($row->getData('listing_id'))) {
            return $accountAndMarketplaceInfo;
        }

        $listingUrl = $this->getUrl('*/ebay_listing/view', array('id' => $row->getData('listing_id')));
        $listingTitle = $this->getHelper('Data')->escapeHtml($row->getData('listing_title'));

        return '<strong>' . $this->__('M2E Pro Listing') . ':</strong>'
            . '&nbsp;<a href="'.$listingUrl.'" target="_blank">'.$listingTitle.'</a><br/>'
            . $accountAndMarketplaceInfo;
    }

    private function getSkuHtml($row)
    {
        $sku = $row->getData('product_sku');
        if (is_null($sku) && !is_null($row->getData('product_id'))) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('product_id'))
                ->getSku();
        }

        $onlineSku = $row->getData('online_sku');
        !empty($onlineSku) && $sku = $onlineSku;

        if (!$sku && $row->getData('is_m2epro_listing')) {
            return '';
        }

        if (!$row->getData('is_m2epro_listing') && is_null($sku)) {
            $sku = '<i style="color:gray;">' . $this->__('receiving') . '...</i>';
        } else if (!$row->getData('is_m2epro_listing') && !$sku) {
            $sku = '<i style="color:gray;">' . $this->__('none') . '</i>';
        } else {
            $sku = $this->getHelper('Data')->escapeHtml($sku);
        }

        return '<strong>'. $this->__('SKU') . ':</strong>&nbsp;' . $sku;
    }

    public function callbackColumnIsInStock($value, $row, $column, $isExport)
    {
        if (is_null($row->getData('is_in_stock'))) {
            return $this->__('N/A');
        }

        if ((int)$row->getData('is_in_stock') <= 0) {
            return '<span style="color: red;">'.$value.'</span>';
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
                'item_id' => $row->getData('ebay_item_id'),
                'account_id' => $row->getData('account_id'),
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

        $currency = $row->getCurrency();

        if (strpos($currency, ',') !== false) {
            $currency = $this->ebayFactory
                ->getObjectLoaded('Marketplace',$row->getMarketplaceId())
                ->getChildObject()->getCurrency();
        }

        if (!empty($onlineStartPrice)) {

            $onlineReservePrice = $row->getData('online_reserve_price');
            $onlineBuyItNowPrice = $row->getData('online_buyitnow_price');

            $onlineStartStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineStartPrice);

            $startPriceText = $this->__('Start Price');

            $onlineCurrentPriceHtml = '';
            $onlineReservePriceHtml = '';
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

        $onlineMinPriceStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineMinPrice);
        $onlineMaxPriceStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineMaxPrice);

        return '<span class="product-price-value">' . $onlineMinPriceStr . '</span>' .
        (($onlineMinPrice != $onlineMaxPrice) ? ' - ' . $onlineMaxPriceStr :  '');
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

        $listingProductId = (int)$row->getData('listing_product_id');
        $listingOtherId   = (int)$row->getData('listing_other_id');

        /** @var \Ess\M2ePro\Model\Processing\Lock[] $processingLocks */
        $processingLocks = array();

        if (!empty($listingProductId)) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);
            $processingLocks = $listingProduct->getProcessingLocks();
        } elseif (!empty($listingOtherId)) {
            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', $listingOtherId);
            $processingLocks = $listingOther->getProcessingLocks();
        }

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

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $altTitle = $this->getHelper('Data')->escapeHtml($this->__('Go to Listing'));
        $iconSrc = $this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png');

        if ($row->getData('is_m2epro_listing')) {
            $url = $this->getUrl('*/ebay_listing/view/', array(
                'view_mode' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher::VIEW_MODE_EBAY,
                'id' => $row->getData('listing_id'),
                'filter' => base64_encode(
                    'product_id[from]='.(int)$row->getData('product_id')
                    .'&product_id[to]='.(int)$row->getData('product_id')
                )
            ));
        } else {
            $url = $this->getUrl('*/ebay_listing_other/view/', array(
                'account' => $row->getData('account_id'),
                'marketplace' => $row->getData('marketplace_id'),
                'filter' => base64_encode(
                    'item_id='.$row->getData('ebay_item_id')
                )
            ));
        }

        return <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$altTitle}" target="_blank" href="{$url}"><img src="{$iconSrc}" /></a>
</div>
HTML;
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'product_name LIKE ? OR product_sku LIKE ? OR listing_title LIKE ?'.
            ' OR online_sku LIKE ? OR online_title LIKE ?', '%'.$value.'%'
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
            $condition .= 'min_online_price <= \''.$value['to'].'\'';
        }

        $condition = '(' . $condition . ') OR (';

        if (isset($value['from']) && $value['from'] != '') {
            $condition .= 'max_online_price >= \''.$value['from'].'\'';
        }
        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $condition .= ' AND ';
            }
            $condition .= 'max_online_price <= \''.$value['to'].'\'';
        }

        $condition .= ')';

        $collection->getSelect()->where($condition);
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
}