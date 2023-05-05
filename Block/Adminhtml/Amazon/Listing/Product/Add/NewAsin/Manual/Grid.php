<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin\Manual;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    protected $magentoProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType */
    private $productTypeResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product */
    private $amazonListingProductResource;

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;
        $this->globalDataHelper = $globalDataHelper;
        $this->productTypeFactory = $productTypeFactory;
        $this->productTypeResource = $productTypeResource;
        $this->listingProductResource = $listingProductResource;
        $this->amazonListingProductResource = $amazonListingProductResource;
        parent::__construct($context, $backendHelper, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('listing_for_products_add');

        // Initialization block
        // ---------------------------------------
        $this->setId('newAsinManualGrid');
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
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setListingProductModeOn();

        $collection
            ->setListing($this->listing)
            ->setStoreId($this->listing->getData('store_id'))
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');
        // ---------------------------------------

        // ---------------------------------------
        $listingProductsIds = $this->listing
            ->getSetting('additional_data', 'adding_new_asin_listing_products_ids');

        $collection->joinTable(
            ['lp' => $this->listingProductResource->getMainTable()],
            'product_id=entity_id',
            [
                'id' => 'id',
            ],
            '{{table}}.listing_id=' . (int)$this->listing->getId()
        );

        $collection->joinTable(
            ['alp' => $this->amazonListingProductResource->getMainTable()],
            'listing_product_id=id',
            [
                'listing_product_id' => 'listing_product_id',
                'template_product_type_id' => 'template_product_type_id',
            ]
        );

        $collection->getSelect()->where('lp.id IN (?)', $listingProductsIds);
        $collection->getSelect()->where(
            'alp.search_settings_status != ? OR alp.search_settings_status IS NULL',
            \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS
        );
        $collection->getSelect()->where('alp.general_id IS NULL');
        // ---------------------------------------

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => $this->__('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => $this->__('Product Title / Product SKU'),
            'align' => 'left',
            'width' => '400px',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterProductTitle'],
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
            'field' => 'id',
            'sortable' => false,
            'filter' => false,
            'actions' => [],
        ];

        $actions = [
            [
                'caption' => $this->__('Set Product Type'),
                'field' => 'id',
                'onclick_action' => 'ListingGridObj.setProductTypeRowAction',
            ],
            [
                'caption' => $this->__('Reset Product Type'),
                'field' => 'id',
                'onclick_action' => 'ListingGridObj.resetProductTypeRowAction',
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
            'label' => $this->__('Set Product Type'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('resetProductType', [
            'label' => $this->__('Reset Product Type'),
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

        $value .= '<br/><strong>' . $this->__('SKU') .
            ':</strong> ' . $this->dataHelper->escapeHtml($sku) . '<br/>';

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $listingProductId = (int)$row->getData('id');
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$amazonListingProduct->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        if ($amazonListingProduct->getVariationManager()->isRelationParentType()) {
            $productAttributes = (array)$amazonListingProduct->getVariationManager()
                                                             ->getTypeModel()->getProductAttributes();
        } else {
            $productOptions = $amazonListingProduct->getVariationManager()
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
        $productTypeId = $row->getData('template_product_type_id');

        if (empty($productTypeId)) {
            $label = $this->__('Not Selected');

            return <<<HTML
<span class='icon-warning' style="color: gray; font-style: italic;">{$label}</span>
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

    // ---------------------------------------

    protected function callbackColumnProductTypeFilterCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        if ($value) {
            $collection->addFieldToFilter('template_product_type_id', ['notnull' => null]);
        } else {
            $collection->addFieldToFilter('template_product_type_id', ['null' => null]);
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
        $isNotExistProductsWithProductTypeTemplate = (int)$this->isNotExistProductsWithProductTypeTemplate();

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Plugin/Messages'
    ],function(MessageObj) {

        var button = $('add_products_new_asin_manual_continue');
        if ({$isNotExistProductsWithProductTypeTemplate}) {
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

    protected function isNotExistProductsWithProductTypeTemplate()
    {
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->getCollection();
        $countSelect = clone $collection->getSelect();
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);

        $countSelect->columns('COUNT(*)');
        $countSelect->where('alp.template_product_type_id > 0');

        return !$collection->getConnection()->fetchOne($countSelect);
    }
}
