<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Magento;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Magento\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $magentoProductCollectionFactory;
    protected $amazonFactory;
    protected $resourceConnection;
    protected $websiteFactory;
    protected $status;
    protected $type;
    protected $visibility;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;
        $this->resourceConnection = $resourceConnection;
        $this->websiteFactory = $websiteFactory;
        $this->status = $status;
        $this->type = $type;
        $this->visibility = $visibility;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('view_listing');

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingViewMagentoGrid'.$this->listing['id']);
        // ---------------------------------------

        $this->hideMassactionColumn = true;
        $this->hideMassactionDropDown = true;
        $this->showAdvancedFilterProductsOption = false;
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->getSelect()->group('e.entity_id');
        $collection->setStoreId($this->listing->getStoreId());
        $collection->setListing($this->listing->getId());

        $collection
            ->addAttributeToSelect('name')
            ->joinStockItem();

        // ---------------------------------------

        // Join listing product tables
        // ---------------------------------------
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id'              => 'id',
                'amazon_status'   => 'status',
                'additional_data' => 'additional_data'
            ],
            [
                'listing_id' => (int)$this->listing['id']
            ]
        );

        $alpTable = $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['alp' => $alpTable],
            'listing_product_id=id',
            [
                'general_id'        => 'general_id',
                'amazon_sku'        => 'sku',
                'online_qty'        => 'online_qty',
                'online_regular_price'      => 'online_regular_price',
                'online_regular_sale_price' => 'online_regular_sale_price',
                'is_afn_channel'    => 'is_afn_channel'
            ],
            null,
            'left'
        );
        // ---------------------------------------

        // Set filter store
        // ---------------------------------------
        $store = $this->_getStore();

        if ($store->getId()) {
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
            $collection->joinAttribute(
                'magento_price',
                'catalog_product/price',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'thumbnail',
                'catalog_product/thumbnail',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
        } else {
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
            $collection->addAttributeToSelect('thumbnail');
        }
        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($collection);

        $this->getCollection()->addWebsiteNamesToResult();

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'    => $this->__('ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnProductId']
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Title'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'escape'       => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle']
        ]);

        $this->addColumn('type', [
            'header'    => $this->__('Type'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'options',
            'sortable'  => false,
            'index'     => 'type_id',
            'filter_index' => 'type_id',
            'options' => $this->getProductTypes()
        ]);

        $this->addColumn('is_in_stock', [
            'header'    => $this->__('Stock Availability'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'options',
            'sortable'  => false,
            'index'     => 'is_in_stock',
            'filter_index' => 'is_in_stock',
            'options' => [
                '1' => $this->__('In Stock'),
                '0' => $this->__('Out of Stock')
            ],
            'frame_callback' => [$this, 'callbackColumnIsInStock']
        ]);

        $this->addColumn('sku', [
            'header'    => $this->__('SKU'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'text',
            'index'     => 'sku',
            'filter_index' => 'sku'
        ]);

        $store = $this->_getStore();

        $priceAttributeAlias = 'price';
        if ($store->getId()) {
            $priceAttributeAlias = 'magento_price';
        }

        $this->addColumn($priceAttributeAlias, [
            'header'    => $this->__('Price'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'price',
            'filter'    => 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index'     => $priceAttributeAlias,
            'filter_index' => $priceAttributeAlias,
            'frame_callback' => [$this, 'callbackColumnPrice']
        ]);

        $this->addColumn('qty', [
            'header'    => $this->__('QTY'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'qty',
            'filter_index' => 'qty',
            'frame_callback' => [$this, 'callbackColumnQty']
        ]);

        $this->addColumn('visibility', [
            'header'    => $this->__('Visibility'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'options',
            'sortable'  => false,
            'index'     => 'visibility',
            'filter_index' => 'visibility',
            'options' => $this->visibility->getOptionArray()
        ]);

        $this->addColumn('status', [
            'header'    => $this->__('Status'),
            'align'     => 'left',
            'width'     => '90px',
            'type'      => 'options',
            'sortable'  => false,
            'index'     => 'status',
            'filter_index' => 'status',
            'options' => $this->status->getOptionArray(),
            'frame_callback' => [$this, 'callbackColumnStatus']
        ]);

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn('websites', [
                'header'    => $this->__('Websites'),
                'align'     => 'left',
                'width'     => '90px',
                'type'      => 'options',
                'sortable'  => false,
                'index'     => 'websites',
                'filter_index' => 'websites',
                'options'   => $this->websiteFactory->create()->getCollection()->toOptionHash()
            ]);
        }

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $rowVal = $row->getData();

        if ($column->getId() == 'magento_price' &&
            (!isset($rowVal['magento_price']) || (float)$rowVal['magento_price'] <= 0)
        ) {
            $value = '<span style="color: red;">0</span>';
        }

        if ($column->getId() == 'price' &&
            (!isset($rowVal['price']) || (float)$rowVal['price'] <= 0)
        ) {
            $value = '<span style="color: red;">0</span>';
        }

        return $value;
    }

    //########################################

    public function getRowUrl($row)
    {
        return $this->getUrl('*/amazon_listing/view', ['_current'=>true]);
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

    //########################################

    protected function _getStore()
    {
        return $this->_storeManager->getStore((int)$this->listing->getStoreId());
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingGridHandlerObj.afterInitPage();
JS
            );
        }

        return parent::_toHtml();
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

    protected function isShowRuleBlock()
    {
        /** @var $ruleModel \Ess\M2ePro\Model\Magento\Product\Rule */
        $ruleModel = $this->getHelper('Data\GlobalData')->getValue('rule_model');

        if ($ruleModel->isEmpty()) {
            return false;
        }

        return parent::isShowRuleBlock();
    }

    //########################################
}
