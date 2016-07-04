<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add;

abstract class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{    
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $productFactory;
    protected $type;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->productFactory = $productFactory;
        $this->type = $type;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        $this->setId('ebayListingProductGrid'.$this->listing->getId());
        // ---------------------------------------

        $this->hideMassactionDropDown = true;
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->productFactory->create()->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id');

        $collection->joinTable(
            array('cisi' => 'cataloginventory_stock_item'),
            'product_id=entity_id',
            array(
                'qty' => 'qty',
                'is_in_stock' => 'is_in_stock'
            ),
            '{{table}}.stock_id=1',
            'left'
        );

        // ---------------------------------------
        $collection->getSelect()->distinct();
        // ---------------------------------------

        // Set filter store
        // ---------------------------------------
        $store = $this->_getStore();

        if ($store->getId()) {
            $collection->joinAttribute(
                'price', 'catalog_product/price', 'entity_id', NULL, 'left', $store->getId()
            );
            $collection->joinAttribute(
                'status', 'catalog_product/status', 'entity_id', NULL, 'inner',$store->getId()
            );
            $collection->joinAttribute(
                'visibility', 'catalog_product/visibility', 'entity_id', NULL, 'inner',$store->getId()
            );
            $collection->joinAttribute(
                'thumbnail', 'catalog_product/thumbnail', 'entity_id', NULL, 'left',$store->getId()
            );
        } else {
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
            $collection->addAttributeToSelect('thumbnail');
        }
        // ---------------------------------------

        // Hide products others listings
        // ---------------------------------------
        $prefix = $this->getHelper('Data\GlobalData')->getValue('hide_products_others_listings_prefix');
        is_null($hideParam = $this->getHelper('Data\Session')->getValue($prefix)) && $hideParam = true;

        if ($hideParam || isset($this->listing['id'])) {

            $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
            $dbExcludeSelect = $collection->getConnection()
                ->select()
                ->from($lpTable, new \Zend_Db_Expr('DISTINCT `product_id`'));

            if ($hideParam) {

                $lTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
                $dbExcludeSelect->join(
                    array('l' => $lTable),
                    '`l`.`id` = `listing_id`', NULL
                );

                $dbExcludeSelect->where('`l`.`account_id` = ?', $this->listing['account_id']);
                $dbExcludeSelect->where('`l`.`marketplace_id` = ?', $this->listing['marketplace_id']);
                $dbExcludeSelect->where('`l`.`component_mode` = ?',\Ess\M2ePro\Helper\Component\Ebay::NICK);

            } else {
                $dbExcludeSelect->where('`listing_id` = ?',(int)$this->listing['id']);
            }

            $collection->getSelect()
                ->joinLeft(array('sq' => $dbExcludeSelect), 'sq.product_id = e.entity_id', array())
                ->where('sq.product_id IS NULL');
        }
        // ---------------------------------------

        $collection->addFieldToFilter(
            array(
                array('attribute'=>'type_id','neq'=>'virtual'),
            )
        );

        $this->setCollection($collection);
        
        $this->getCollection()->addWebsiteNamesToResult();

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => $this->__('ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('name', array(
            'header'    => $this->__('Title'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnProductTitle')
        ));

        $types = $this->type->getOptionArray();
        unset($types['virtual']);

        $this->addColumn('type', array(
            'header'    => $this->__('Type'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'options',
            'sortable'  => false,
            'index'     => 'type_id',
            'filter_index' => 'type_id',
            'options'   => $types
        ));

        $this->addColumn('is_in_stock', array(
            'header'    => $this->__('Stock Availability'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'options',
            'sortable'  => false,
            'index'     => 'is_in_stock',
            'filter_index' => 'is_in_stock',
            'options' => array(
                '1' => $this->__('In Stock'),
                '0' => $this->__('Out of Stock')
            ),
            'frame_callback' => array($this, 'callbackColumnIsInStock')
        ));

        $this->addColumn('sku', array(
            'header'    => $this->__('SKU'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'text',
            'index'     => 'sku',
            'filter_index' => 'sku'
        ));

        $store = $this->_getStore();

        $this->addColumn('price', array(
            'header'    => $this->__('Price'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'price',
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index'     => 'price',
            'filter_index' => 'price',
            'frame_callback' => array($this, 'callbackColumnPrice')
        ));

        $this->addColumn('qty', array(
            'header'    => $this->__('QTY'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'qty',
            'filter_index' => 'qty',
            'frame_callback' => array($this, 'callbackColumnQty')
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');

        return parent::_prepareMassaction();
    }

    //########################################

    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if ($column->getId() == 'websites') {
                $this->getCollection()->joinField(
                    'websites',
                    'catalog_product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left'
                );
            }
        }
        return parent::_addColumnFilterToCollection($column);
    }
    
    /**
     * @return \Magento\Store\Model\Store
     */
    protected function _getStore()
    {
        // Get store filter
        // ---------------------------------------
        $storeId = 0;
        if (isset($this->listing['store_id'])) {
            $storeId = (int)$this->listing['store_id'];
        }
        // ---------------------------------------

        return $this->_storeManager->getStore($storeId);
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::_toHtml();
        }

        // ---------------------------------------
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Ebay\Listing\AutoAction', array('id' => $this->listing->getId())
        ));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Ebay\Listing\Product\Add', array('_current' => true)
        ));

        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing_product_add', array('_current' => true, 'step' => null)),
            'ebay_listing_product_add'
        );

        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing_product_category_settings', array('_current' => true, 'step' => null)),
            'ebay_listing_product_category_settings'
        );
        // ---------------------------------------

        // ---------------------------------------
        $this->jsTranslator->addTranslations([
            'eBay Categories' => $this->__('eBay Categories'),
            'of Product' => $this->__('of Product'),
            'Specifics' => $this->__('Specifics'),
            'Auto Add/Remove Rules' => $this->__('Auto Add/Remove Rules'),
            'Based on Magento Categories' => $this->__('Based on Magento Categories'),
            'You must select at least 1 Category.' =>
                $this->__('You must select at least 1 Category.'),
            'Rule with the same Title already exists.' =>
                $this->__('Rule with the same Title already exists.'),
            'Listing Settings Customization' => $this->__('Listing Settings Customization'),
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $showAutoActionPopup = !$this->getHelper('Module')->getConfig()->getGroupValue(
            '/view/ebay/advanced/autoaction_popup/', 'shown'
        );
        $showAutoActionPopup = json_encode($showAutoActionPopup);

        // ---------------------------------------

        $this->js->add(
<<<JS
    require([
        'M2ePro/Ebay/Listing/Product/Add',
        'M2ePro/Plugin/AreaWrapper',
        'M2ePro/Plugin/ProgressBar',
        'M2ePro/Ebay/Listing/AutoAction'
    ], function(){

        window.WrapperObj = new AreaWrapper('add_products_container');
        window.ProgressBarObj = new ProgressBar('add_products_progress_bar');

        window.ListingProductAddObj = new EbayListingProductAdd({
            show_autoaction_popup: {$showAutoActionPopup},

            get_selected_products: {$this->getSelectedProductsCallback()}
        });

        window.ListingAutoActionObj = new EbayListingAutoAction();
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    abstract protected function getSelectedProductsCallback();

    //########################################
}