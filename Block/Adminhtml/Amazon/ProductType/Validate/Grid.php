<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\ProductType\Validate;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    protected $magentoProductCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product */
    private $amazonListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType */
    private $productTypeDictionaryResource;
    /** @var array */
    private $listingProductIds;
    /** @var \Ess\M2ePro\Model\Magento\ProductFactory */
    private $magentoProductFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\Validation */
    private $productTypeValidationResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType */
    private $productTypeResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\Magento\ProductFactory $magentoProductFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType $productTypeDictionaryResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\Validation $productTypeValidationResource,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $listingProductIds,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->dataHelper = $dataHelper;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->listingProductResource = $listingProductResource;
        $this->amazonListingProductResource = $amazonListingProductResource;
        $this->productTypeDictionaryResource = $productTypeDictionaryResource;
        $this->magentoProductFactory = $magentoProductFactory;
        $this->listingProductIds = $listingProductIds;
        $this->productTypeValidationResource = $productTypeValidationResource;
        $this->productTypeResource = $productTypeResource;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId($this->getGridSelectorId());

        $this->setDefaultSort('product_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $listingProductsIds = $this->listingProductIds;

        $collection = $this->magentoProductCollectionFactory->create();
        $collection
            ->setListingProductModeOn()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');

        $collection->joinTable(
            ['lp' => $this->listingProductResource->getMainTable()],
            'product_id = entity_id',
            [
                'id' => 'id',
                'additional_data' => 'additional_data',
            ]
        );

        $collection->joinTable(
            ['alp' => $this->amazonListingProductResource->getMainTable()],
            'listing_product_id = id',
            [
                'product_type_id' => 'template_product_type_id',
            ],
            '{{table}}.variation_parent_id is NULL'
        );

        $collection->getSelect()->joinLeft(
            ['tpt' => $this->productTypeResource->getMainTable()],
            'tpt.id = alp.template_product_type_id',
            []
        );

        $collection->getSelect()->joinLeft(
            ['pt' => $this->productTypeDictionaryResource->getMainTable()],
            'pt.id = tpt.dictionary_product_type_id',
            [
                'product_type_title' => 'title',
            ]
        );

        $collection->getSelect()->joinLeft(
            ['vd' => $this->productTypeValidationResource->getMainTable()],
            'lp.id = vd.listing_product_id',
            [
                'validation_status' => 'status',
                'validation_error_messages' => 'error_messages',
            ]
        );

        $collection->getSelect()->where('alp.general_id IS NULL');
        $collection->getSelect()->where('alp.template_product_type_id IS NOT NULL');
        $collection->getSelect()->where('lp.id IN (?)', $listingProductsIds);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('product_type', [
            'header' => __('Product Type'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'product_type_title',
            'filter_index' => 'product_type',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductType'],
            'filter_condition_callback' => [$this, 'callbackFilterProductType'],
        ]);

        $this->addColumn('status', [
            'header' => __('Product Data'),
            'sortable' => false,
            'align' => 'center',
            'index' => 'validation_status',
            'filter_index' => 'validation_status',
            'type' => 'options',
            'options' => [
                \Ess\M2ePro\Model\Amazon\ProductType\Validation::STATUS_INVALID => __('Incomplete'),
                \Ess\M2ePro\Model\Amazon\ProductType\Validation::STATUS_VALID => __('Complete')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ]);

        $this->addColumn('errors', [
            'header' => __('Error'),
            'width' => '200px',
            'index' => 'validation_error_messages',
            'filter_index' => 'validation_error_messages',
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnErrors'],
            'filter_condition_callback' => [$this, 'callbackFilterColumnErrors'],
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        $this->getMassactionBlock()->addItem('validateProductType', [
            'label' => __('Validate Product Data'),
            'url' => '',
        ]);

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport): string
    {
        if ($productTitle === '') {
            return __('N/A');
        }

        $value = sprintf(
            '<span>%s</span>',
            $this->dataHelper->escapeHtml($productTitle)
        );

        $productSku = $row->getData('sku');
        if ($productSku === null) {
            $magentoProduct = $this->magentoProductFactory->create();
            $magentoProduct->setProductId((int)$row->getData('entity_id'));
            $productSku = $magentoProduct->getSku();
        }

        $value .= sprintf(
            '<br><strong>%s</strong>: %s',
            __('SKU'),
            $this->dataHelper->escapeHtml($productSku)
        );

        return $value;
    }

    protected function callbackFilterTitle($collection, $column): void
    {
        $value = $column->getFilter()->getValue();

        if ($value === null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
            ]
        );
    }

    public function callbackColumnProductType($value, $row, $column, $isExport): string
    {
        if ($value === '') {
            return __('N/A');
        }

        $productTypeId = (int)$row->getData('product_type_id');

        return sprintf(
            '<a target="_blank" href="%s">%s</a>',
            $this->getUrl('*/amazon_template_productType/edit', [
                'id' => $productTypeId,
                'close_on_save' => true
            ]),
            $this->dataHelper->escapeHtml($value)
        );
    }

    protected function callbackFilterProductType($collection, $column): void
    {
        $value = $column->getFilter()->getValue();

        if ($value === null) {
            return;
        }

        $collection->getSelect()->where('pt.title LIKE ?', "%$value%");
    }

    public function callbackColumnStatus($value, $row, $column, $isExport): string
    {
        $status = $row->getData('validation_status');
        if ($status === null) {
            return '';
        }

        if ((int)$status === \Ess\M2ePro\Model\Amazon\ProductType\Validation::STATUS_VALID) {
            return sprintf('<span style="color: green">%s</span>', __('Complete'));
        }

        return sprintf('<span style="color: red">%s</span>', __('Incomplete'));
    }

    protected function callbackFilterStatus($collection, $column): void
    {
        $value = $column->getFilter()->getValue();

        if ($value === null) {
            return;
        }

        $collection->getSelect()->where('vd.status = ?', $value);
    }

    public function callbackColumnErrors($value, $row, $column, $isExport): string
    {
        $errorMessages = \Ess\M2ePro\Helper\Json::decode($row->getData('validation_error_messages') ?: '[]');

        if (!$errorMessages) {
            return '';
        }

        $errorList = [];
        foreach ($errorMessages as $message) {
            $errorList[] = sprintf('<li>%s</li>', $message);
        }

        return sprintf(
            '<div class="product-type-validation-grid-error-message-block"><ul>%s</ul></div>',
            implode('', $errorList)
        );
    }

    public function callbackFilterColumnErrors($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value === null) {
            return;
        }

        $collection->getSelect()->where('vd.error_messages LIKE ?', "%$value%");
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _toHtml()
    {
        $this->importTextsToJs();
        $this->importUrlsToJs();
        $this->initJs();

        $progressBarHtml = sprintf('<div id="%s"></div>', $this->getProgressBarSelectorId());

        return $progressBarHtml . parent::_toHtml();
    }

    private function importTextsToJs(): void
    {
        $this->jsTranslator->addTranslations([
            'progress_bar_title' => __('Product Data Validation'),
            'progress_bar_status' => __('Validation in progress...'),
        ]);
    }

    private function importUrlsToJs(): void
    {
        $this->jsUrl->addUrls([
            'product_type_validation_url' =>
                $this->getUrl('*/amazon_productType_validation/validate'),
        ]);
    }

    private function initJs(): void
    {
        $isModalCall = \Ess\M2ePro\Helper\Json::encode($this->isModalCall());
        $gridSelector = \Ess\M2ePro\Helper\Json::encode($this->getId());
        $progressBarSelector = \Ess\M2ePro\Helper\Json::encode($this->getProgressBarSelectorId());

        $js = /** @lang JavaScript */ <<<JAVASCRIPT
require([
    'M2ePro/Amazon/ProductType/Validator/Grid'
],function() {
    var objectName = $isModalCall ? 'ProductTypeValidatorGridModalObj' : 'ProductTypeValidatorGridObj';
    var validatorGridObject;
    if (typeof window[objectName] === "undefined") {
        validatorGridObject = new ProductTypeValidatorGrid($gridSelector, $progressBarSelector);
        window['productTypeValidatorObjectName'] = objectName;
        window[objectName] = validatorGridObject
        validatorGridObject.afterInitPage();
        validatorGridObject.validateAll();
    } else {
        validatorGridObject = window[objectName];
        validatorGridObject.afterInitPage();
   }
});
JAVASCRIPT;

        $this->js->add($js);
    }

    private function isModalCall(): bool
    {
        return (bool)$this->getRequest()->getParam('modal', false);
    }

    private function getGridSelectorId(): string
    {
        $id = 'product_type_validation_grid';
        if ($this->getRequest()->getParam('modal', false)) {
            $id .= '_modal';
        }

        return $id;
    }

    private function getProgressBarSelectorId(): string
    {
        $id = 'product_type_validation_progress_bar';
        if ($this->isModalCall()) {
            $id .= '_modal';
        }

        return $id;
    }
}
