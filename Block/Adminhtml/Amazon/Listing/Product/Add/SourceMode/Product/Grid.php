<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Product;

use Ess\M2ePro\Block\Adminhtml\Listing\Product\ShowOthersListingsProductsFilter;
use Ess\M2ePro\Model\ResourceModel\Magento\Product\Filter\ExcludeSimpleProductsInVariation;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    private $listing;

    protected $magentoProductCollectionFactory;
    protected $type;
    protected $websiteFactory;

    protected \Ess\M2ePro\Helper\Magento\Product $magentoProductHelper;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Filter\ExcludeSimpleProductsInVariation */
    private ExcludeSimpleProductsInVariation $excludeSimpleProductsInVariation;

    public function __construct(
        ExcludeSimpleProductsInVariation $excludeSimpleProductsInVariation,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Ess\M2ePro\Helper\Magento\Product $magentoProductHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->type = $type;
        $this->websiteFactory = $websiteFactory;
        $this->magentoProductHelper = $magentoProductHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->excludeSimpleProductsInVariation = $excludeSimpleProductsInVariation;
        parent::__construct($context, $backendHelper, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('listing_for_products_add');

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

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Product\Grid
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create()
                                                            ->addAttributeToSelect('sku')
                                                            ->addAttributeToSelect('name')
                                                            ->addAttributeToSelect('type_id');

        $collection->setStoreId($this->listing->getStoreId());
        $collection->addStoreFilter();
        $collection->joinStockItem();

        // ---------------------------------------
        $collection->getSelect()->group('e.entity_id');
        // ---------------------------------------

        // Hide products others listings
        // ---------------------------------------
        $hideParam = true;
        if ($this->getRequest()->has(ShowOthersListingsProductsFilter::PARAM_NAME_SHOW_PRODUCT_IN_OTHER_LISTING)) {
            $hideParam = false;
        }

        $excludeProductsExpr = null;
        if ($hideParam || isset($this->listing['id'])) {
            $excludeProductsExpr = $this->getExcludedProductsExpression($hideParam);

            $collection->getSelect()
                       ->joinLeft(
                           $excludeProductsExpr,
                           'true',
                           []
                       )
                       ->where('lp.product_id IS NULL');
        }
        // ---------------------------------------

        $includeSimpleProductsInVariation = $this
            ->getRequest()
            ->has(ShowOthersListingsProductsFilter::PARAM_NAME_SHOW_CHILD_PRODUCTS_IN_VARIATIONS);
        if (
            !$includeSimpleProductsInVariation
            && !empty($this->listing['id'] ?? 0)
        ) {
            $this->excludeSimpleProductsInVariation->filter($collection, (int)$this->listing['id']);
        }

        $collection->addFieldToFilter(
            [
                [
                    'attribute' => 'type_id',
                    'in' => $this->magentoProductHelper->getOriginKnownTypes(),
                ],
            ]
        );

        $collection->addWebsiteNamesToResult();
        $this->setCollection($collection);

        if ($excludeProductsExpr) {
            $originalCollection = clone $collection;
            $this->applyQueryFilters();
            $countSelect = $collection->getSelectCountSql();
            $countSelect
                ->joinLeft(
                    $excludeProductsExpr,
                    'true',
                    []
                );

            $originalCollection->setLeftJoinsImportant(true)
                               ->setCustomCountSelect($countSelect);
            $this->setCollection($originalCollection);
        }

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $baseCollection = $this->getCollection();
        $collection = $this->magentoProductCollectionFactory->create();

        $entityIds = [];
        foreach ($baseCollection->getItems() as $item) {
            $entityIds[] = $item->getData('entity_id');
        }

        $collection->addFieldToFilter('entity_id', ['in' => $entityIds]);

        // Set filter store
        // ---------------------------------------
        $store = $this->_getStore();

        if ($store->getId()) {
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                0
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
                'price',
                'catalog_product/price',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
            $collection->joinAttribute(
                'thumbnail',
                'catalog_product/thumbnail',
                'entity_id',
                null,
                'left',
                0
            );
        } else {
            $collection->addAttributeToSelect('name');
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
            $collection->addAttributeToSelect('thumbnail');
        }
        // ---------------------------------------

        $collection->load();
        $attributeData = [];
        foreach ($collection->getItems() as $item) {
            $attributeData[$item->getData('entity_id')] = [
                'name' => $item->getData('name'),
                'price' => $item->getData('price'),
                'status' => $item->getData('status'),
                'visibility' => $item->getData('visibility'),
                'thumbnail' => $item->getData('thumbnail'),
            ];
        }

        foreach ($baseCollection->getItems() as $item) {
            $entityId = $item->getData('entity_id');

            if (!array_key_exists($entityId, $attributeData)) {
                continue;
            }

            $item->setData('name', $attributeData[$entityId]['name']);
            $item->setData('price', $attributeData[$entityId]['price']);
            $item->setData('status', $attributeData[$entityId]['status']);
            $item->setData('visibility', $attributeData[$entityId]['visibility']);
            $item->setData('thumbnail', $attributeData[$entityId]['thumbnail']);
        }

        return parent::_afterLoadCollection();
    }

    /**
     * @param bool $hideParam
     *
     * @return \Zend_Db_Expr
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getExcludedProductsExpression(bool $hideParam): \Zend_Db_Expr
    {
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $lTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        if ($hideParam) {
            return new \Zend_Db_Expr(
                '(`' . $lpTable . '` AS `lp`
INNER JOIN `' . $lTable . '` AS `l`
ON l.id = lp.listing_id
    AND l.account_id = ' . $this->listing['account_id'] . ' AND
    l.marketplace_id = ' . $this->listing['marketplace_id'] . ' AND
    l.component_mode = \'' . \Ess\M2ePro\Helper\Component\Amazon::NICK . '\')
    ON lp.product_id = e.entity_id INNER JOIN (SELECT 1 AS t)'
            );
        }

        return new \Zend_Db_Expr(
            '(`' . $lpTable . '` AS `lp`
INNER JOIN `' . $lTable . '` AS `l`
ON l.id = lp.listing_id
    AND l.id = ' . $this->listing['id'] . ')
    ON lp.product_id = e.entity_id INNER JOIN (SELECT 1 AS t)'
        );
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => __('ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'store_id' => $this->_getStore()->getId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Title'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
        ]);

        $this->addColumn('type', [
            'header' => __('Type'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'type_id',
            'filter_index' => 'type_id',
            'options' => $this->getProductTypes(),
        ]);

        $this->addColumn('is_in_stock', [
            'header' => __('Stock Availability'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'is_in_stock',
            'filter_index' => 'is_in_stock',
            'options' => [
                '1' => __('In Stock'),
                '0' => __('Out of Stock'),
            ],
            'frame_callback' => [$this, 'callbackColumnIsInStock'],
        ]);

        $this->addColumn('sku', [
            'header' => __('SKU'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'text',
            'index' => 'sku',
            'filter_index' => 'sku',
        ]);

        $store = $this->_getStore();

        $this->addColumn('price', [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'price',
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Price::class,
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'index' => 'price',
            'filter_index' => 'price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
        ]);

        $this->addColumn('qty', [
            'header' => __('QTY'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'qty',
            'filter_index' => 'qty',
            'frame_callback' => [$this, 'callbackColumnQty'],
        ]);

        $this->addColumn('visibility', [
            'header' => __('Visibility'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'visibility',
            'filter_index' => 'visibility',
            'options' => \Magento\Catalog\Model\Product\Visibility::getOptionArray(),
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'align' => 'left',
            'width' => '90px',
            'type' => 'options',
            'sortable' => false,
            'index' => 'status',
            'filter_index' => 'status',
            'options' => \Magento\Catalog\Model\Product\Attribute\Source\Status::getOptionArray(),
            'frame_callback' => [$this, 'callbackColumnStatus'],
        ]);

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn('websites', [
                'header' => __('Websites'),
                'align' => 'left',
                'width' => '90px',
                'type' => 'options',
                'sortable' => false,
                'index' => 'websites',
                'filter_index' => 'websites',
                'options' => $this->websiteFactory->create()->getCollection()->toOptionHash(),
            ]);
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
            $selectItemsMessage => __($selectItemsMessage),
            $createEmptyListingMessage => __($createEmptyListingMessage),
            $showAdvancedFilterButtonText => __($showAdvancedFilterButtonText),
            $hideAdvancedFilterButtonText => __($hideAdvancedFilterButtonText),
        ]);
        // ---------------------------------------

        $this->jsUrl->add(
            $this->getUrl('*/amazon_listing_product_add/add', ['_current' => true, 'step' => null]),
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
        $this->js->add(
            <<<JS
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
        $knownTypes = $this->magentoProductHelper->getOriginKnownTypes();

        foreach ($magentoProductTypes as $type => $magentoProductTypeLabel) {
            if (in_array($type, $knownTypes)) {
                continue;
            }

            unset($magentoProductTypes[$type]);
        }

        return $magentoProductTypes;
    }
}
