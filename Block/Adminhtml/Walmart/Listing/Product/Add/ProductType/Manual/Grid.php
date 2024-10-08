<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\ProductType\Manual;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    protected ?\Ess\M2ePro\Model\Listing $listing = null;
    protected $magentoProductCollectionFactory;
    protected $walmartFactory;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->walmartFactory = $walmartFactory;
        $this->globalDataHelper = $globalDataHelper;
        $this->productTypeRepository = $productTypeRepository;

        parent::__construct($context, $backendHelper, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('listing_for_products_add');

        // Initialization block
        // ---------------------------------------
        $this->setId('productTypeManualGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->useAdvancedFilter = false;
    }

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setListingProductModeOn();

        $collection
            ->setListing($this->listing)
            ->setStoreId($this->listing->getData('store_id'))
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');
        // ---------------------------------------

        // ---------------------------------------
        $listingProductsIds = $this->listing->getSetting('additional_data', 'adding_listing_products_ids');

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id' => 'id',
            ],
            '{{table}}.listing_id=' . (int)$this->listing->getId()
        );
        $wlpTable = $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['wlp' => $wlpTable],
            'listing_product_id=id',
            [
                'listing_product_id' => 'listing_product_id',
                'product_type_id' => 'product_type_id',
            ]
        );

        $collection->getSelect()->where('lp.id IN (?)', $listingProductsIds);
        // ---------------------------------------

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Product SKU'),
            'align' => 'left',
            'width' => '400px',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterProductTitle'],
        ]);

        $this->addColumn('category_template', [
            'header' => __('Product Type'),
            'align' => 'left',
            'width' => '*',
            'sortable' => false,
            'type' => 'options',
            'index' => 'category_template_id',
            'filter_index' => 'category_template_id',
            'options' => [
                1 => __('Product Type Selected'),
                0 => __('Product Type Not Selected'),
            ],
            'frame_callback' => [$this, 'callbackColumnProductTypeCallback'],
            'filter_condition_callback' => [$this, 'callbackColumnProductTypeFilterCallback'],
        ]);

        $actionsColumn = [
            'header' => __('Actions'),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'no_link' => true,
            'align' => 'center',
            'width' => '130px',
            'type' => 'text',
            'field' => 'id',
            'sortable' => false,
            'filter' => false,
            'actions' => [],
        ];

        $actions = [
            [
                'caption' => __('Set Product Type'),
                'field' => 'id',
                'onclick_action' => 'ListingGridObj.setProductTypeRowAction',
            ],
        ];

        $actionsColumn['actions'] = $actions;

        $this->addColumn('actions', $actionsColumn);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('listing_product_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('setProductType', [
            'label' => __('Set Product Type'),
            'url' => '',
        ]);

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        if (strlen($productTitle) > 60) {
            $productTitle = substr($productTitle, 0, 60) . '...';
        }

        $productTitle = $this->dataHelper->escapeHtml($productTitle);

        $value = '<span>' . $productTitle . '</span>';

        $sku = $row->getData('sku');

        $value .= '<br/><strong>' . __('SKU') .
            ':</strong> ' . $this->dataHelper->escapeHtml($sku) . '<br/>';

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $listingProductId = (int)$row->getData('id');
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);
        $walmartListingProduct = $listingProduct->getChildObject();

        if (!$walmartListingProduct->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        if ($walmartListingProduct->getVariationManager()->isRelationParentType()) {
            $productAttributes = (array)$walmartListingProduct->getVariationManager()
                                                              ->getTypeModel()->getProductAttributes();
        } else {
            $productOptions = $walmartListingProduct->getVariationManager()
                                                    ->getTypeModel()->getProductOptions();
            $productAttributes = !empty($productOptions) ? array_keys($productOptions) : [];
        }

        if (!empty($productAttributes)) {
            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin-left: 7px"><br/>';
            $value .= implode(', ', $productAttributes);
            $value .= '</div>';
        }

        return $value;
    }

    public function callbackColumnProductTypeCallback($value, $row, $column, $isExport)
    {
        $productTypeId = $row->getData('product_type_id');

        if (empty($productTypeId)) {
            $label = __('Not Selected');

            return <<<HTML
<span class='icon-warning' style="color: gray; font-style: italic;">{$label}</span>
HTML;
        }

        $templateCategoryEditUrl = $this->getUrl('*/walmart_productType/edit', [
            'id' => $productTypeId,
        ]);

        $productType = $this->productTypeRepository->get((int)$productTypeId);

        $title = $this->dataHelper->escapeHtml($productType->getTitle());

        return <<<HTML
<a target="_blank" href="{$templateCategoryEditUrl}">{$title}</a>
HTML;
    }

    protected function callbackFilterProductTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
            ]
        );
    }

    protected function callbackColumnProductTypeFilterCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        if ($value) {
            $collection->addFieldToFilter('product_type_id', ['notnull' => null]);
        } else {
            $collection->addFieldToFilter('product_type_id', ['null' => null]);
        }
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _toHtml()
    {
        $errorMessage = $this
            ->__(
                "To proceed, the category data must be specified.
                  Please select a relevant Product Type for at least one product."
            );
        $isNotExistProductsWithDescriptionTemplate = (int)$this->isNotExistProductsWithProductType();

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Plugin/Messages'
    ],function(MessageObj) {

        var button = $('add_products_productType_manual_continue');
        if ({$isNotExistProductsWithDescriptionTemplate}) {
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

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingGridObj.afterInitPage();
JS
            );
        }

        return parent::_toHtml();
    }

    protected function isNotExistProductsWithProductType()
    {
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->getCollection();
        $countSelect = clone $collection->getSelect();
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);

        $countSelect->columns('COUNT(*)');
        $countSelect->where('wlp.product_type_id > 0');

        return !$collection->getConnection()->fetchOne($countSelect);
    }
}
