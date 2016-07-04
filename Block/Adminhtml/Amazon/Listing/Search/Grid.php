<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search;

use \Ess\M2ePro\Block\Adminhtml\Listing\Search\Switcher;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $localeCurrency;
    protected $customCollectionFactory;
    protected $amazonFactory;
    protected $resourceConnection;
    protected $resourceStockItem;
    protected $resourceCatalogProduct;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
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
        $this->amazonFactory = $amazonFactory;
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
        $this->setId('amazonListingSearchGrid');
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
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->getSelect()->distinct();
        $listingProductCollection->getSelect()
                   ->join(array('l'=>$this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
                                '(`l`.`id` = `main_table`.`listing_id`)',
                                array('listing_title'=>'title','store_id','marketplace_id'))
                   ->join(array(
                       'al'=>$this->activeRecordFactory->getObject('Amazon\Listing')->getResource()->getMainTable()
                   ),
                                '(`al`.`listing_id` = `l`.`id`)',
                                array('template_selling_format_id'));
        // ---------------------------------------

        // only parents and individuals
        $listingProductCollection->getSelect()->where('second_table.variation_parent_id IS NULL');

        // Communicate with magento product table
        // ---------------------------------------
        $dbSelect = $this->resourceConnection->getConnection()
                                     ->select()
                                     ->from($this->resourceConnection
                                                            ->getTableName('catalog_product_entity_varchar'),
                                                                           new \Zend_Db_Expr('MAX(`store_id`)'))
                                     ->where("`entity_id` = `main_table`.`product_id`")
                                     ->where("`attribute_id` = `ea`.`attribute_id`")
                                     ->where("`store_id` = 0 OR `store_id` = `l`.`store_id`");

        $listingProductCollection->getSelect()
                //->join(array('csi'=>Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock_item')),
//                             '(csi.product_id = `main_table`.product_id)',array('qty'))
                   ->join(array('cpe'=>$this->resourceConnection->getTableName('catalog_product_entity')),
                                '(cpe.entity_id = `main_table`.product_id)',
                                array('magento_sku'=>'sku'))
                   ->join(array('cisi'=>$this->resourceConnection
                                                ->getTableName('cataloginventory_stock_item')),
                                '(cisi.product_id = `main_table`.product_id AND cisi.stock_id = 1)',
                                array('is_in_stock'))
                   ->join(array('cpev'=>$this->resourceConnection
                                                ->getTableName('catalog_product_entity_varchar')),
                                "(`cpev`.`entity_id` = `main_table`.product_id)",
                                array('value'))
                   ->join(array('ea'=>$this->resourceConnection->getTableName('eav_attribute')),
                                '(`cpev`.`attribute_id` = `ea`.`attribute_id` AND `ea`.`attribute_code` = \'name\')',
                                array())
                   ->where('`cpev`.`store_id` = ('.$dbSelect->__toString().')');
        // ---------------------------------------

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            array(
                'is_m2epro_listing'             => new \Zend_Db_Expr('1'),
                'magento_sku'                   => 'cpe.sku',
                'is_in_stock'                   => 'cisi.is_in_stock',
                'product_name'                  => 'cpev.value',
                'listing_title'                 => 'l.title',
                'store_id'                      => 'l.store_id',
                'account_id'                    => 'l.account_id',
                'marketplace_id'                => 'l.marketplace_id',
                'template_selling_format_id'    => 'al.template_selling_format_id',
                'listing_product_id'            => 'main_table.id',
                'product_id'                    => 'main_table.product_id',
                'listing_id'                    => 'main_table.listing_id',
                'status'                        => 'main_table.status',
                'is_general_id_owner'           => 'second_table.is_general_id_owner',
                'general_id'                    => 'second_table.general_id',
                'is_afn_channel'                => 'second_table.is_afn_channel',
                'is_variation_parent'           => 'second_table.is_variation_parent',
//                'is_repricing'                  => 'second_table.is_repricing',
                'variation_child_statuses'      => 'second_table.variation_child_statuses',
                'online_sku'                    => 'second_table.sku',
                'online_qty'                    => 'second_table.online_qty',
                'online_price'                  => 'second_table.online_price',
                'online_sale_price'             => 'second_table.online_sale_price',
                'online_sale_price_start_date'  => 'second_table.online_sale_price_start_date',
                'online_sale_price_end_date'    => 'second_table.online_sale_price_end_date',
                'min_online_price'                     => 'IF(
                    (`t`.`variation_min_price` IS NULL),
                    IF(
                      `second_table`.`online_sale_price_start_date` IS NOT NULL AND
                      `second_table`.`online_sale_price_end_date` IS NOT NULL AND
                      `second_table`.`online_sale_price_start_date` <= CURRENT_DATE() AND
                      `second_table`.`online_sale_price_end_date` >= CURRENT_DATE(),
                      `second_table`.`online_sale_price`,
                      `second_table`.`online_price`
                    ),
                    `t`.`variation_min_price`
                )',
                'max_online_price'                     => 'IF(
                    (`t`.`variation_max_price` IS NULL),
                    IF(
                      `second_table`.`online_sale_price_start_date` IS NOT NULL AND
                      `second_table`.`online_sale_price_end_date` IS NOT NULL AND
                      `second_table`.`online_sale_price_start_date` <= CURRENT_DATE() AND
                      `second_table`.`online_sale_price_end_date` >= CURRENT_DATE(),
                      `second_table`.`online_sale_price`,
                      `second_table`.`online_price`
                    ),
                    `t`.`variation_max_price`
                )'
            )
        );
        $listingProductCollection->getSelect()->joinLeft(
            new \Zend_Db_Expr('(
                SELECT
                    `malp`.`variation_parent_id`,
                    MIN(
                        IF(
                            `malp`.`online_sale_price_start_date` IS NOT NULL AND
                            `malp`.`online_sale_price_end_date` IS NOT NULL AND
                            `malp`.`online_sale_price_start_date` <= CURRENT_DATE() AND
                            `malp`.`online_sale_price_end_date` >= CURRENT_DATE(),
                            `malp`.`online_sale_price`,
                            `malp`.`online_price`
                        )
                    ) as variation_min_price,
                    MAX(
                        IF(
                            `malp`.`online_sale_price_start_date` IS NOT NULL AND
                            `malp`.`online_sale_price_end_date` IS NOT NULL AND
                            `malp`.`online_sale_price_start_date` <= CURRENT_DATE() AND
                            `malp`.`online_sale_price_end_date` >= CURRENT_DATE(),
                            `malp`.`online_sale_price`,
                            `malp`.`online_price`
                        )
                    ) as variation_max_price
                FROM `'. $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable()
                    .'` as malp
                INNER JOIN `'. $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()
                    .'` AS `mlp`
                    ON (`malp`.`listing_product_id` = `mlp`.`id`)
                WHERE `mlp`.`status` IN (
                    ' . \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED . ',
                    ' . \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED . ',
                    ' . \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN . '
                ) AND `malp`.`variation_parent_id` IS NOT NULL
                GROUP BY `malp`.`variation_parent_id`
            )'),
            'second_table.listing_product_id=t.variation_parent_id',
            array(
                'variation_min_price' => 'variation_min_price',
                'variation_max_price' => 'variation_max_price',
            )
        );

        // ---------------------------------------
        $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->getSelect()->distinct();

        // add stock availability, type id, status & visibility to select
        // ---------------------------------------
        $listingOtherCollection->getSelect()
            ->joinLeft(
                array('cisi' => $this->resourceStockItem->getMainTable()),
                '(`cisi`.`product_id` = `main_table`.`product_id` AND cisi.stock_id = 1)',
                array('is_in_stock'))
            ->joinLeft(array('cpe'=>$this->resourceConnection->getTableName('catalog_product_entity')),
                '(cpe.entity_id = `main_table`.product_id)',
                array('magento_sku'=>'sku'));
        // ---------------------------------------

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            array(
                'is_m2epro_listing'             => new \Zend_Db_Expr(0),
                'magento_sku'                   => 'cpe.sku',
                'is_in_stock'                   => 'cisi.is_in_stock',
                'product_name'                  => 'second_table.title',
                'listing_title'                 => new \Zend_Db_Expr('NULL'),
                'store_id'                      => new \Zend_Db_Expr(0),
                'account_id'                    => 'main_table.account_id',
                'marketplace_id'                => 'main_table.marketplace_id',
                'template_selling_format_id'    => new \Zend_Db_Expr('NULL'),
                'listing_product_id'            => new \Zend_Db_Expr('NULL'),
                'product_id'                    => 'main_table.product_id',
                'listing_id'                    => new \Zend_Db_Expr('NULL'),
                'status'                        => 'main_table.status',
                'is_general_id_owner'           => new \Zend_Db_Expr('NULL'),
                'general_id'                    => 'second_table.general_id',
                'is_afn_channel'                => 'second_table.is_afn_channel',
                'is_variation_parent'           => new \Zend_Db_Expr('NULL'),
//                'is_repricing'             => 'second_table.is_repricing',
                'variation_child_statuses'      => new \Zend_Db_Expr('NULL'),
                'online_sku'                    => 'second_table.sku',
                'online_qty'                    => 'second_table.online_qty',
                'online_price'                  => 'second_table.online_price',
                'online_sale_price'             => new \Zend_Db_Expr('NULL'),
                'online_sale_price_start_date'  => new \Zend_Db_Expr('NULL'),
                'online_sale_price_end_date'    => new \Zend_Db_Expr('NULL'),
                'min_online_price'              => 'second_table.online_price',
                'max_online_price'              => 'second_table.online_price',
                'variation_min_price'           => new \Zend_Db_Expr('NULL'),
                'variation_max_price'           => new \Zend_Db_Expr('NULL')
            )
        );
        // ---------------------------------------

        // ---------------------------------------
        $selects = array(
            $listingProductCollection->getSelect(),
            $listingOtherCollection->getSelect()
        );

        $unionSelect = $this->resourceConnection->getConnection()->select();
        $unionSelect->union($selects);

        $resultCollection = $this->customCollectionFactory->create();
        $resultCollection->setConnection($this->resourceConnection->getConnection());
        $resultCollection->getSelect()->reset()->from(
            array('main_table' => $unionSelect),
            array(
                'is_m2epro_listing',
                'magento_sku',
                'is_in_stock',
                'product_name',
                'listing_title',
                'store_id',
                'account_id',
                'marketplace_id',
                'template_selling_format_id',
                'listing_product_id',
                'product_id',
                'listing_id',
                'status',
                'is_general_id_owner',
                'general_id',
                'is_afn_channel',
                'is_variation_parent',
//                'is_repricing',
                'variation_child_statuses',
                'online_sku',
                'online_qty',
                'online_price',
                'online_sale_price',
                'online_sale_price_start_date',
                'online_sale_price_end_date',
                'min_online_price',
                'max_online_price',
                'variation_min_price',
                'variation_max_price'
            )
        );

        // ---------------------------------------

        $accountId = (int)$this->getRequest()->getParam('amazonAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('amazonMarketplace', false);
        $listingType = (int)$this->getRequest()->getParam('listing_type', false);

        if ($accountId) {
            $resultCollection->getSelect()->where('account_id = ?', $accountId);
        }

        if ($marketplaceId) {
            $resultCollection->getSelect()->where('marketplace_id = ?', $marketplaceId);
        }

        if ($listingType) {

            if ($listingType == Switcher::LISTING_TYPE_M2E_PRO) {

                $resultCollection->getSelect()->where('is_m2epro_listing = ?', 1);

            } elseif ($listingType == Switcher::LISTING_TYPE_LISTING_OTHER) {

                $resultCollection->getSelect()->where('is_m2epro_listing = ?', 0);
            }
        }

        // Set collection to grid
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
            'filter_index' => 'product_id',
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

        $this->addColumn('sku', array(
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'online_sku',
            'filter_index' => 'online_sku',
            'frame_callback' => array($this, 'callbackColumnAmazonSku')
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

        $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);

        if ($dir == 'desc') {
            $priceSortField = 'max_online_price';
        } else {
            $priceSortField = 'min_online_price';
        }

        $priceColumn = array(
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '110px',
            'type' => 'number',
            'index' => $priceSortField,
            'filter_index' => $priceSortField,
            'frame_callback' => array($this, 'callbackColumnPrice'),
            'filter_condition_callback' => array($this, 'callbackFilterPrice')
        );

//        if ($this->getHelper('Component\Amazon')->isRepricingEnabled()) {
//            $priceColumn['filter'] = 'M2ePro/adminhtml_common_amazon_grid_column_filter_price';
//        }

        $this->addColumn('online_price', $priceColumn);

        $this->addColumn('status', array(
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

        $withoutImageHtml = '<a href="'
                            .$this->getUrl('catalog/product/edit',
                                           array('id' => $productId))
                            .'" target="_blank">'
                            .$productId
                            .'</a>';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')->getConfig()
                                                                       ->getGroupValue('/view/',
                                                                                      'show_products_thumbnails');
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
        $value = '<div style="margin-bottom: 5px">'.$this->getHelper('Data')->escapeHtml($value).'</div>';

        if (is_null($row->getData('listing_id'))) {
            $account = $this->amazonFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
            $marketplace = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

            $value .= '<strong>' . $this->__('3rd Party Listings') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . ', ' . $marketplace->getTitle() . '<br/>';

        } else {
            $urlParams = array();
            $urlParams['id'] = $row->getData('listing_id');
            $urlParams['back'] = $this->getHelper('Data')->makeBackUrlParam('*/adminhtml_amazon_listing/search');

            $listingUrl = $this->getUrl('*/amazon_listing/view',$urlParams);
            $listingTitle = $this->getHelper('Data')->escapeHtml($row->getData('listing_title'));

            if (strlen($listingTitle) > 50) {
                $listingTitle = substr($listingTitle, 0, 50) . '...';
            }

            $value .= '<strong>'
                      .$this->__('M2E Pro Listing')
                      .': </strong><a href="'
                      .$listingUrl
                      .'">'
                      .$listingTitle
                      .'</a>';
        }

        if (!is_null($row->getData('magento_sku'))) {
            $tempSku = $row->getData('magento_sku');

            $value .= '<br/><strong>'
                . $this->__('SKU')
                . ':</strong> '
                . $this->getHelper('Data')->escapeHtml($tempSku);
        }

        if (is_null($row->getData('listing_product_id'))) {
            return $value;
        }

        $listingProductId = (int)$row->getData('listing_product_id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product',$listingProductId);
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if ($variationManager->isVariationParent()) {
            $productAttributes = $listingProduct->getChildObject()->getVariationManager()
                ->getTypeModel()->getProductAttributes();

            $virtualProductAttributes = $variationManager->getTypeModel()->getVirtualProductAttributes();
            $virtualChannelAttributes = $variationManager->getTypeModel()->getVirtualChannelAttributes();

            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey;"><br/>';
            $attributesStr = '';
            if (empty($virtualProductAttributes) && empty($virtualChannelAttributes)) {
                $attributesStr = implode(', ', $productAttributes);
            } else {
                foreach ($productAttributes as $attribute) {
                    if (in_array($attribute, array_keys($virtualProductAttributes))) {

                        $attributesStr .= '<span style="border-bottom: 2px dotted grey">' . $attribute .
                            ' (' . $virtualProductAttributes[$attribute] . ')</span>, ';

                    } else if (in_array($attribute, array_keys($virtualChannelAttributes))) {

                        $attributesStr .= '<span>' . $attribute .
                            ' (' . $virtualChannelAttributes[$attribute] . ')</span>, ';

                    } else {
                        $attributesStr .= $attribute . ', ';
                    }
                }
                $attributesStr = rtrim($attributesStr, ', ');
            }
            $value .= $attributesStr;
            $value .= '</div>';
        }

        if ($variationManager->isIndividualType() &&
            $variationManager->getTypeModel()->isVariationProductMatched()
        ) {
            $productOptions = $variationManager->getTypeModel()->getProductOptions();

            $value .= '<br/>';
            $value .= '<div style="font-size: 11px; color: grey;"><br/>';
            foreach ($productOptions as $attribute => $option) {
                !$option && $option = '--';
                $value .= '<strong>' . $this->getHelper('Data')->escapeHtml($attribute) .
                    '</strong>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option) . '<br/>';
            }
            $value .= '</div>';
            $value .= '<br/>';
        }

        return $value;
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

                $afn = $this->__('AFN');
                $total = $this->__('Total');
                $inStock = $this->__('In Stock');
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
        onclick="AmazonListingAfnQtyObj.showAfnQty(this,'{$sku}','{$productId}',{$accountId})">
        {$afn}
    </a>
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

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED ||
            $row->getData('general_id') == '') {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $variationChildStatuses = json_decode($row->getData('variation_child_statuses'), true);

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

        if ($value == 0 && (bool)$row->getData('is_afn_channel')) {
            return $this->__('AFN');
        }

        return $value . '<br/>' . $this->__('AFN');
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $repricingHtml ='';

        /*
        if (!$row->getData('is_variation_parent') &&
            Mage::helper('M2ePro/Component_Amazon')->isRepricingEnabled() &&
            (int)$row->getData('is_repricing') === \Ess\M2ePro\Model\Amazon\Listing\Product::IS_REPRICING_YES) {

            $text = $this->__(
                'This product is used by Amazon Repricing Tool.
                     The Price cannot be updated through the M2E Pro.'
            );

            $repricingHtml = <<<HTML
<span style="float:right; text-align: left;">&nbsp;
    <img class="tool-tip-image"
         style="vertical-align: middle; width: 16px;"
         src="{$this->getSkinUrl('M2ePro/images/money.png')}">
    <span class="tool-tip-message tool-tip-message tip-left" style="display:none;">
        <img src="{$this->getSkinUrl('M2ePro/images/i_icon.png')}">
        <span>{$text}</span>
    </span>
</span>
HTML;
        }
        */

        $onlineMinPrice = $row->getData('min_online_price');
        $onlineMaxPrice = $row->getData('max_online_price');

        if (is_null($onlineMinPrice) || $onlineMinPrice === '') {
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
            ->getCachedObjectLoaded('Marketplace',$marketplaceId)
            ->getChildObject()
            ->getDefaultCurrency();

        if ($row->getData('is_variation_parent')) {
            $onlineMinPriceStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineMinPrice);
            $onlineMaxPriceStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineMaxPrice);

            return $onlineMinPriceStr . (($onlineMinPrice != $onlineMaxPrice) ? ' &ndash; ' . $onlineMaxPriceStr :  '');
        }

        $onlinePrice = $row->getData('online_price');
        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($onlinePrice);
        }

        $resultHtml = '';

        $salePrice = $row->getData('online_sale_price');
        if (!$row->getData('is_variation_parent') && (float)$salePrice > 0) {
            $currentTimestamp = strtotime($this->getHelper('Data')->getCurrentGmtDate(false,'Y-m-d 00:00:00'));

            $startDateTimestamp = strtotime($row->getData('online_sale_price_start_date'));
            $endDateTimestamp   = strtotime($row->getData('online_sale_price_end_date'));

            if ($currentTimestamp <= $endDateTimestamp) {
                $fromDate = $this->_localeDate->formatDate(
                    $row->getData('online_sale_price_start_date'), \IntlDateFormatter::MEDIUM
                );
                $toDate = $this->_localeDate->formatDate(
                    $row->getData('online_sale_price_end_date'), \IntlDateFormatter::MEDIUM
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

        return $resultHtml;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        switch ($row->getData('status')) {

            case \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN:
            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                $value = '<span style="color: gray;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $value = '<span style="color: green;">' . $value . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $value = '<span style="color: red;">'.$value.'</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $value = '<span style="color: orange; font-weight: bold;">'.$value.'</span>';
                break;

            default:
                break;
        }

        if (is_null($row->getData('listing_product_id'))) {
            return $value;
        }

        $listingProductId = (int)$row->getData('listing_product_id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product',$listingProductId);

        $tempLocks = $listingProduct->getProcessingLocks();

        foreach ($tempLocks as $lock) {

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

                case 'delete_and_remove_action':
                    $value .= '<br/><span style="color: #605fff">[Remove in Progress...]</span>';
                    break;

//                case 'switch_to_afn_action':
//                    $value .= '<br/><span style="color: #605fff">[Switch to AFN in Progress...]</span>';
//                    break;

                case 'switch_to_mfn_action':
                    $value .= '<br/><span style="color: #605fff">[Switch to MFN in Progress...]</span>';
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
            $url = $this->getUrl('*/amazon_listing/view/',array(
                'id' => $row->getData('listing_id'),
                'filter' => base64_encode(
                    'product_id[from]='.(int)$row->getData('product_id')
                    .'&product_id[to]='.(int)$row->getData('product_id')
                )
            ));
        } else {
            $url = $this->getUrl('*/amazon_listing_other/view/', array(
                'account' => $row->getData('account_id'),
                'marketplace' => $row->getData('marketplace_id'),
                'filter' => base64_encode(
                    'title='.$row->getData('online_sku')
                )
            ));
        }

        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$altTitle}" target="_blank" href="{$url}"><img src="{$iconSrc}" alt="{$altTitle}" /></a>
</div>
HTML;

        return $html;
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()
            ->where('product_name LIKE ? OR magento_sku LIKE ? OR listing_title LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= 'online_qty >= ' . $value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $where .= 'online_qty <= ' . $value['to'];
        }

        if (!empty($value['afn'])) {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }
            $where .= 'is_afn_channel = ' . \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_YES;;
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $condition = '';

        if (isset($value['from']) || isset($value['to'])) {

            if (isset($value['from']) && $value['from'] != '') {
                $condition = 'min_online_price >= \'' . $value['from'] . '\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'min_online_price <= \'' . $value['to'] . '\'';
            }

            $condition = '(' . $condition . ') OR (';

            if (isset($value['from']) && $value['from'] != '') {
                $condition .= 'max_online_price >= \'' . $value['from'] . '\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'max_online_price <= \'' . $value['to'] . '\'';
            }

            $condition .= ')';

        }

        /*
        if (Mage::helper('M2ePro/Component_Amazon')->isRepricingEnabled() && !empty($value['is_repricing'])) {
            if (!empty($condition)) {
                $condition = '(' . $condition . ') OR ';
            }
            $condition .= 'is_repricing = ' . \Ess\M2ePro\Model\Amazon\Listing\Product::IS_REPRICING_YES;
        }
        */

        $collection->getSelect()->where($condition);
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
        $this->jsUrl->add($this->getUrl('*/amazon_listing/getAFNQtyBySku'), 'amazon_listing/getAFNQtyBySku');

        $this->js->addRequireJs([
            'alq' => 'M2ePro/Amazon/Listing/AfnQty'
        ], <<<JS
        window.AmazonListingAfnQtyObj = new AmazonListingAfnQty();
JS
        );

        return parent::_toHtml();
    }

    //########################################
}