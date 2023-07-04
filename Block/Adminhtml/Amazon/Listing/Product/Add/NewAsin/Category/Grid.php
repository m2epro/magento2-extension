<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin\Category;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Category\Grid
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    protected $magentoProductCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Magento\Category */
    protected $magentoCategoryHelper;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType */
    private $productTypeResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory
     * @param \Ess\M2ePro\Helper\Magento\Category $magentoCategoryHelper
     * @param \Ess\M2ePro\Model\ResourceModel\Magento\Category\CollectionFactory $categoryCollectionFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Helper\Magento\Category $magentoCategoryHelper,
        \Ess\M2ePro\Model\ResourceModel\Magento\Category\CollectionFactory $categoryCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->resourceConnection = $resourceConnection;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->magentoCategoryHelper = $magentoCategoryHelper;
        $this->databaseHelper = $databaseHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->productTypeFactory = $productTypeFactory;
        $this->productTypeResource = $productTypeResource;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        parent::__construct(
            $categoryCollectionFactory,
            $context,
            $backendHelper,
            $dataHelper,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('listing_for_products_add');

        // Initialization block
        // ---------------------------------------
        $this->setId('newAsinCategoryGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->prepareDataByCategories();
    }

    protected function _prepareCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', 'in' => array_keys($this->getData('categories_data'))],
        ]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('magento_category', [
            'header' => $this->__('Magento Category'),
            'align' => 'left',
            'width' => '500px',
            'type' => 'text',
            'index' => 'name',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnMagentoCategory'],
        ]);

        $this->addColumn('product_type', [
            'header' => $this->__('Product Type'),
            'align' => 'left',
            'width' => '*',
            'sortable' => false,
            'type' => 'options',
            'index' => 'product_type_id',
            'filter_index' => 'product_type_id',
            'options' => [
                1 => $this->__('Product Type Selected'),
                0 => $this->__('Product Type Not Selected'),
            ],
            'frame_callback' => [$this, 'callbackColumnProductTypeCallback'],
            'filter_condition_callback' => [$this, 'callbackColumnProductTypeFilterCallback'],
        ]);

        $actionsColumn = [
            'header' => $this->__('Actions'),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'align' => 'center',
            'width' => '130px',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
            'actions' => [],
        ];

        $actions = [
            [
                'caption' => $this->__('Set Product Type'),
                'field' => 'entity_id',
                'onclick_action' => 'ListingGridObj.setProductTypeByCategoryRowAction',
            ],
            [
                'caption' => $this->__('Reset Product Type'),
                'field' => 'entity_id',
                'onclick_action' => 'ListingGridObj.resetProductTypeByCategoryRowAction',
            ],
        ];

        $actionsColumn['actions'] = $actions;

        $this->addColumn('actions', $actionsColumn);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('setProductTypeByCategory', [
            'label' => $this->__('Set Product Type'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('resetProductTypeByCategory', [
            'label' => $this->__('Reset Product Type'),
            'url' => '',
        ]);

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackColumnProductTypeCallback($value, $row, $column, $isExport)
    {
        $categoriesData = $this->getData('categories_data');
        $productsIds = implode(',', $categoriesData[$row->getData('entity_id')]);

        $productTypeData = $this->getData('product_type_data');
        $productTypeIds = [];
        foreach ($categoriesData[$row->getData('entity_id')] as $productId) {
            if (empty($productTypeIds[$productTypeData[$productId]])) {
                $productTypeIds[$productTypeData[$productId]] = 0;
            }
            $productTypeIds[$productTypeData[$productId]]++;
        }

        arsort($productTypeIds);

        reset($productTypeIds);
        $productTypeId = key($productTypeIds);

        if (empty($productTypeId)) {
            $label = $this->__('Not Selected');

            return <<<HTML
<span class="icon-warning" style="color: gray; font-style: italic;">{$label}</span>
<input type="hidden" id="products_ids_{$row->getData('entity_id')}" value="{$productsIds}">
HTML;
        }

        $productTypeEditUrl = $this->getUrl('*/amazon_template_productType/edit', [
            'id' => $productTypeId,
        ]);

        $productType = $this->productTypeFactory->create();
        $this->productTypeResource->load($productType, $productTypeId);

        $title = $this->dataHelper->escapeHtml($productType->getTitle());

        return <<<HTML
<a target="_blank" href="{$productTypeEditUrl}">{$title}</a>
<input type="hidden" id="products_ids_{$row->getData('entity_id')}" value="{$productsIds}">
HTML;
    }

    protected function callbackColumnProductTypeFilterCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $filteredProductsCategories = [];
        $filteredListingProductsIds = [];

        $categoriesData = $this->getData('categories_data');
        $productTypeIds = $this->getData('product_type_data');

        foreach ($productTypeIds as $listingProductId => $productTypeId) {
            if ($productTypeId !== null) {
                $filteredListingProductsIds[] = $listingProductId;
            }
        }

        foreach ($categoriesData as $categoryId => $listingProducts) {
            foreach ($filteredListingProductsIds as $listingProductId) {
                if (in_array($listingProductId, $listingProducts)) {
                    $filteredProductsCategories[] = $categoryId;
                }
            }
        }

        $filteredProductsCategories = array_unique($filteredProductsCategories);

        if ($value) {
            $collection->addFieldToFilter('entity_id', ['in' => $filteredProductsCategories]);
        } elseif (!empty($filteredProductsCategories)) {
            $collection->addFieldToFilter('entity_id', ['nin' => $filteredProductsCategories]);
        }
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _toHtml()
    {
        $categoriesData = $this->getData('categories_data');
        if (!empty($categoriesData)) {
            $errorMessage = $this
                ->__(
                    "To proceed, the category data must be specified.
                     Please select a relevant Product Type for at least one Magento Category. "
                );
            $isNotExistProductsWithProductType = (int)$this
                ->isNotExistProductsWithProductType($this->getData('product_type_data'));

            $this->js->add(
                <<<JS
    require([
        'M2ePro/Plugin/Messages'
    ], function(MessageObj) {
        var button = $('add_products_new_asin_category_continue');
        if ({$isNotExistProductsWithProductType}) {
            button.addClassName('disabled');
            button.disable();
            MessageObj.addError(`{$errorMessage}`);
        } else {
            button.removeClassName('disabled');
            button.enable();
            MessageObj.clear();
        }
    });
JS
            );
        }

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingGridObj.afterInitPage();
JS
            );
        }

        $this->css->add('.grid-listing-column-actions { width:100px; }');

        return parent::_toHtml();
    }

    private function prepareDataByCategories()
    {
        $listingProductsIds = $this->listing
            ->getSetting('additional_data', 'adding_new_asin_listing_products_ids');

        $listingProductCollection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
        ]);
        $listingProductCollection->addFieldToFilter('id', ['in' => $listingProductsIds]);
        $listingProductCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $listingProductCollection->getSelect()->columns([
            'id' => 'id',
            'product_id' => 'product_id',
            'template_product_type_id' => 'second_table.template_product_type_id',
        ]);

        $productsIds = [];
        $productTypeIds = [];
        foreach ($listingProductCollection->getData() as $item) {
            $productsIds[$item['id']] = $item['product_id'];
            $productTypeIds[$item['id']] = $item['template_product_type_id'];
        }
        $productsIds = array_unique($productsIds);

        $categoriesIds = $this->magentoCategoryHelper->getLimitedCategoriesByProducts(
            $productsIds,
            $this->listing->getStoreId()
        );

        $categoriesData = [];

        foreach ($categoriesIds as $categoryId) {
            $collection = $this->magentoProductCollectionFactory->create();
            $collection->setListing($this->listing);
            $collection->setStoreId($this->listing->getStoreId());
            $collection->addFieldToFilter('entity_id', ['in' => $productsIds]);

            $collection->joinTable(
                [
                    'ccp' => $this->databaseHelper
                        ->getTableNameWithPrefix('catalog_category_product'),
                ],
                'product_id=entity_id',
                ['category_id' => 'category_id']
            );
            $collection->addFieldToFilter('category_id', $categoryId);

            $data = $collection->getData();

            foreach ($data as $item) {
                $categoriesData[$categoryId][] = array_search($item['entity_id'], $productsIds);
            }

            $categoriesData[$categoryId] = array_unique($categoriesData[$categoryId]);
        }

        $this->setData('categories_data', $categoriesData);
        $this->setData('product_type_data', $productTypeIds);

        $this->listing->setSetting(
            'additional_data',
            'adding_new_asin_product_type_data',
            $productTypeIds
        );
        $this->listing->save();
    }

    protected function isNotExistProductsWithProductType($productTypesData)
    {
        if (empty($productTypesData)) {
            return true;
        }

        foreach ($productTypesData as $productTypeData) {
            if (!empty($productTypeData)) {
                return false;
            }
        }

        return true;
    }
}
