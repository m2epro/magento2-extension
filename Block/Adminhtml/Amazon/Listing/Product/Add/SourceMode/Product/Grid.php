<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    private $listing;

    protected $magentoProductCollectionFactory;
    protected $type;
    protected $visibility;
    protected $status;
    protected $websiteFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->type = $type;
        $this->visibility = $visibility;
        $this->status = $status;
        $this->websiteFactory = $websiteFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductGrid' . (isset($this->listing['id']) ? $this->listing['id'] : ''));
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->hideMassactionDropDown = true;
        $this->showAdvancedFilterProductsOption = false;
    }

    //########################################

    protected function _prepareCollection()
    {
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id');

        $collection->joinStockItem(array(
            'qty'         => 'qty',
            'is_in_stock' => 'is_in_stock'
        ));

        // ---------------------------------------
        $collection->getSelect()->distinct();
        // ---------------------------------------

        // Set filter store
        // ---------------------------------------
        $store = $this->_getStore();

        if ($store->getId()) {
            $collection->joinAttribute('name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                0);
            $collection->joinAttribute('status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId());
            $collection->joinAttribute('visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId());
            $collection->joinAttribute('price',
                'catalog_product/price',
                'entity_id',
                null,
                'left',
                $store->getId());
            $collection->joinAttribute('thumbnail',
                'catalog_product/thumbnail',
                'entity_id',
                null,
                'left',
                0);
        } else {
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
            $collection->addAttributeToSelect('thumbnail');
        }
        // ---------------------------------------

        // Hide products others listings
        // ---------------------------------------
        $hideParam = true;
        if ($this->getRequest()->has('show_products_others_listings')) {
            $hideParam = false;
        }

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
                $dbExcludeSelect->where('`l`.`component_mode` = ?', \Ess\M2ePro\Helper\Component\Amazon::NICK);

            } else {
                $dbExcludeSelect->where('`listing_id` = ?', (int)$this->listing['id']);
            }

            $collection->getSelect()
                ->joinLeft(array('sq' => $dbExcludeSelect), 'sq.product_id = e.entity_id', array())
                ->where('sq.product_id IS NULL');
        }
        // ---------------------------------------

        $collection->addFieldToFilter(
            array(array(
                'attribute' => 'type_id',
                'in' => $this->getHelper('Magento\Product')->getOriginKnownTypes()
            ))
        );

        $store->getId() && $collection->setStoreId($store->getId());

        $this->setCollection($collection);

        $this->getCollection()->addWebsiteNamesToResult();

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header' => $this->__('ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('name', array(
            'header' => $this->__('Title'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnProductTitle')
        ));

        $this->addColumn('type', array(
            'header' => $this->__('Type'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'type_id',
            'filter_index' => 'type_id',
            'options' => $this->getProductTypes()
        ));

        $this->addColumn('is_in_stock', array(
            'header' => $this->__('Stock Availability'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'is_in_stock',
            'filter_index' => 'is_in_stock',
            'options' => array(
                '1' => $this->__('In Stock'),
                '0' => $this->__('Out of Stock')
            ),
            'frame_callback' => array($this, 'callbackColumnIsInStock')
        ));

        $this->addColumn('sku', array(
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'text',
            'index' => 'sku',
            'filter_index' => 'sku'
        ));

        $store = $this->_getStore();

        $this->addColumn('price', array(
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'price',
            'filter' => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index' => 'price',
            'filter_index' => 'price',
            'frame_callback' => array($this, 'callbackColumnPrice')
        ));

        $this->addColumn('qty', array(
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'qty',
            'filter_index' => 'qty',
            'frame_callback' => array($this, 'callbackColumnQty')
        ));

        $this->addColumn('visibility', array(
            'header' => $this->__('Visibility'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'visibility',
            'filter_index' => 'visibility',
            'options' => $this->visibility->getOptionArray()
        ));

        $this->addColumn('status', array(
            'header' => $this->__('Status'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'status',
            'filter_index' => 'status',
            'options' => $this->status->getOptionArray(),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

        if (!$this->_storeManager->isSingleStoreMode()) {

            $this->addColumn('websites', array(
                'header' => $this->__('Websites'),
                'align' => 'left',
                'width' => '90px',
                'type' => 'options',
                'sortable' => false,
                'index' => 'websites',
                'filter_index' => 'websites',
                'options' => $this->websiteFactory->create()->getCollection()->toOptionHash()
            ));
        }

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
            $this->massactionMassSelectStyleFix();
            return parent::_toHtml();
        }

        $selectItemsMessage = 'Please select the Products you want to perform the Action on.';
        $createEmptyListingMessage = 'Are you sure you want to create empty Listing?';

        $showAdvancedFilterButtonText = 'Show Advanced Filter';
        $hideAdvancedFilterButtonText = 'Hide Advanced Filter';

        // ---------------------------------------
        $this->jsTranslator->addTranslations([
            $selectItemsMessage => $this->__($selectItemsMessage),
            $createEmptyListingMessage => $this->__($createEmptyListingMessage),
            $showAdvancedFilterButtonText => $this->__($showAdvancedFilterButtonText),
            $hideAdvancedFilterButtonText => $this->__($hideAdvancedFilterButtonText)
        ]);
        // ---------------------------------------

        $this->jsUrl->add(
            $this->getUrl('*/amazon_listing_product_add/add', array('_current' => true, 'step' => null)),
            'add_products'
        );

        $this->jsUrl->add($this->getUrl('*/*/index'), 'back');

        $this->js->add(
<<<JS
    require([
        'M2ePro/Amazon/Listing/Product/Add',
        'M2ePro/Listing/Product/Grid',
        'M2ePro/Plugin/AreaWrapper',
        'M2ePro/Plugin/ProgressBar'
    ], function(){

        WrapperObj = new AreaWrapper('add_products_container');
        ProgressBarObj = new ProgressBar('add_products_progress_bar');

        AddListingObj = new AmazonListingProductAdd(ProgressBarObj, WrapperObj);
        AddListingObj.listing_id = '{$this->getRequest()->getParam('id')}';

        ListingProductGridObj = new ListingProductGrid(AddListingObj);
        ListingProductGridObj.setGridId('{$this->getId()}');

    });
JS
        );

        $this->massactionMassSelectStyleFix();

        return parent::_toHtml();
    }

    //########################################

    protected function massactionMassSelectStyleFix()
    {
        $this->js->add(<<<JS
        require([
        'M2ePro/General/PhpFunctions',
    ], function(){

        wait(function() {
            return typeof ProductGridObj != 'undefined';
        }, function() {
          return ProductGridObj.massactionMassSelectStyleFix();
        }, 20);
    });
JS
        );
    }

    //########################################

    protected function getProductTypes()
    {
        $magentoProductTypes = $this->type->getOptionArray();
        $knownTypes = $this->getHelper('Magento\Product')->getOriginKnownTypes();

        foreach ($magentoProductTypes as $type => $magentoProductTypeLabel) {
            if (in_array($type, $knownTypes)) {
                continue;
            }

            unset($magentoProductTypes[$type]);
        }

        return $magentoProductTypes;
    }

    //########################################
}