<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\ValidationStep;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product as WizardProductResource;
use Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory;
use Ess\M2ePro\Helper\Data as DataHelper;
use Ess\M2ePro\Model\Magento\ProductFactory as MagentoProductFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category as CategoryTemplateResource;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Template;
use Magento\Backend\Helper\Data;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private CollectionFactory $magentoProductCollectionFactory;

    private DataHelper $dataHelper;

    /** @var array */
    private array $listingProductIds;

    private MagentoProductFactory $magentoProductFactory;

    private CategoryTemplateResource $ebayTemplateCategoryResource;

    private WizardProductResource $wizardProductResource;

    private WizardRuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        DataHelper $dataHelper,
        CollectionFactory   $magentoProductCollectionFactory,
        MagentoProductFactory $magentoProductFactory,
        CategoryTemplateResource $ebayTemplateCategoryResource,
        WizardProductResource $wizardProductResource,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        Template $context,
        Data $backendHelper,
        array $listingProductIds,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->listingProductIds = $listingProductIds;
        $this->dataHelper = $dataHelper;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->magentoProductFactory = $magentoProductFactory;
        $this->ebayTemplateCategoryResource = $ebayTemplateCategoryResource;
        $this->wizardProductResource = $wizardProductResource;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
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
            ['lp' => $this->wizardProductResource->getMainTable()],
            'magento_product_id = entity_id',
            [
                'validation_status' => 'validation_status',
                'validation_errors' => 'validation_errors',
                'template_category_id' => 'template_category_id',
                'wizard_product_id' => 'id',
            ]
        );

        $collection->joinTable(
            ['template_category' => $this->ebayTemplateCategoryResource->getMainTable()],
            'id = template_category_id',
            [
                'category_main_mode' => 'category_mode',
                'category_main_id' => 'category_id',
                'category_main_path' => 'category_path',
                'category_main_attribute' => 'category_attribute',
                'category_main_is_custom_template' => 'is_custom_template',
            ],
            null,
            'left'
        );

        $collection->getSelect()->where('lp.magento_product_id IN (?)', $listingProductsIds);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', [
            'header' => __('Magento product ID'),
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

        $this->addColumn('category', [
            'header' => $this->__('eBay Categories'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'frame_callback' => [$this, 'callbackColumnCategory'],
            'filter_condition_callback' => [$this, 'callbackFilterCategory'],
        ]);

        $this->addColumn('validation_status', [
            'header' => __('Product Data'),
            'sortable' => false,
            'align' => 'center',
            'index' => 'validation_status',
            'filter_index' => 'validation_status',
            'type' => 'options',
            'options' => [
                \Ess\M2ePro\Model\Ebay\Category\Specific\Validation\Result::STATUS_INVALID => __('Incomplete'),
                \Ess\M2ePro\Model\Ebay\Category\Specific\Validation\Result::STATUS_VALID => __('Complete'),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ]);

        $this->addColumn('validation_errors', [
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
        $this->setMassactionIdField('wizard_product_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        $this->getMassactionBlock()->addItem('validateEbayCategorySpecific', [
            'label' => __('Validate Specific'),
            'url' => '',
        ]);

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

    public function callbackColumnCategory($value, $row, $column, $isExport): string
    {
        if ($row->getData('category_main_id') === null) {
            return 'Primary category is not set';
        }

        return sprintf(
            '%s (%s)',
            $this->dataHelper->escapeHtml($row->getData('category_main_path')),
            $row->getData('category_main_id')
        );
    }

    protected function callbackFilterCategory($collection, $column): void
    {
        $value = $column->getFilter()->getValue();

        $fieldsToFilter = [
            ['attribute' => 'category_main_path', 'like' => '%' . $value . '%'],
        ];

        if (is_numeric($value)) {
            $fieldsToFilter[] = ['attribute' => 'category_main_id', 'eq' => $value];
        }

        $collection->addFieldToFilter($fieldsToFilter);
    }

    public function callbackColumnStatus($value, $row, $column, $isExport): string
    {
        $status = $row->getData('validation_status');
        if ($status === null) {
            return '';
        }

        if ((int)$status === \Ess\M2ePro\Model\Ebay\Category\Specific\Validation\Result::STATUS_VALID) {
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

        $collection->addFieldToFilter('validation_status', ['eq' => $value]);
    }

    public function callbackColumnErrors($value, $row, $column, $isExport): string
    {
        $errorMessages = \Ess\M2ePro\Helper\Json::decode($row->getData('validation_errors') ?: '[]');

        if (!$errorMessages) {
            return '';
        }

        $errorList = [];
        foreach ($errorMessages as $message) {
            $errorList[] = sprintf('<li>%s</li>', array_shift($message));
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

        $collection->addFieldToFilter('validation_error_messages', ['like' => "%$value%"]);
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
            'progress_bar_title' => __('Specific Validation'),
            'progress_bar_status' => __('Validation in progress...'),
        ]);
    }

    private function importUrlsToJs(): void
    {
        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_validation/validate',
                [
                    'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ]
            ),
            'ebay_category_specific_validation_url'
        );
    }

    private function initJs(): void
    {
        $isModalCall = \Ess\M2ePro\Helper\Json::encode($this->isModalCall());
        $gridSelector = \Ess\M2ePro\Helper\Json::encode($this->getId());
        $progressBarSelector = \Ess\M2ePro\Helper\Json::encode($this->getProgressBarSelectorId());

        $js = /** @lang JavaScript */ <<<JAVASCRIPT
require([
    'M2ePro/Ebay/Category/Specific/Validation/Grid'
],function() {
    var objectName = $isModalCall ? 'EbayCategorySpecificValidatorGridModalObj' : 'EbayCategorySpecificValidatorGridObj';
    var validatorGridObject;
    if (typeof window[objectName] === "undefined") {
        validatorGridObject = new EbayCategorySpecificValidatorGrid($gridSelector, $progressBarSelector);
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
        $id = 'ebay_category_specific_validation_grid';
        if ($this->getRequest()->getParam('modal', false)) {
            $id .= '_modal';
        }

        return $id;
    }

    private function getProgressBarSelectorId(): string
    {
        $id = 'ebay_category_specific_validation_progress_bar';
        if ($this->isModalCall()) {
            $id .= '_modal';
        }

        return $id;
    }
}
