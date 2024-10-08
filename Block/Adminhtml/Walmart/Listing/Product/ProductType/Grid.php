<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\ProductType;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private int $marketplaceId;
    private array $productsIds;
    private string $mapToTemplateJsFn = 'ListingGridObj.productTypeHandler.mapToProductType';
    private string $createNewTemplateJsFn = 'ListingGridObj.productTypeHandler.createInNewTab';
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType\CollectionFactory $productTypeCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $dictionaryProductTypeResource;

    public function __construct(
        int $marketplaceId,
        array $productsIds,
        \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType\CollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $dictionaryProductTypeResource,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->marketplaceId = $marketplaceId;
        $this->productsIds = $productsIds;
        $this->dataHelper = $dataHelper;
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
        $this->dictionaryProductTypeResource = $dictionaryProductTypeResource;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartProductTypeGrid');

        $this->setFilterVisibility();
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $this->setNoTemplatesText();

        $collection = $this->productTypeCollectionFactory->create();
        $collection->getSelect()
                   ->join(
                       ['adpt' => $this->dictionaryProductTypeResource->getMainTable()],
                       'adpt.id = main_table.dictionary_product_type_id',
                       ['product_type_title' => 'adpt.title']
                   );
        $collection->getSelect()
                   ->where('adpt.marketplace_id = ?', $this->marketplaceId);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header' => (string)__('Title'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'filter_index' => 'main_table.title',
            'escape' => false,
            'sortable' => true,
            'frame_callback' => [$this, 'callbackColumnTitle'],
        ]);

        $this->addColumn('action', [
            'header' => (string)__('Action'),
            'align' => 'left',
            'type' => 'number',
            'index' => 'id',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnAction'],
        ]);
    }

    protected function _prepareLayout()
    {
        $this->setChild(
            'refresh_button',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                 ->setData([
                     'id' => 'product_type_refresh_btn',
                     'label' => (string)__('Refresh'),
                     'class' => 'action primary',
                     'onclick' => "ListingGridObj.productTypeHandler.loadGrid()",
                 ])
        );

        return parent::_prepareLayout();
    }

    public function getRefreshButtonHtml()
    {
        return $this->getChildHtml('refresh_button');
    }

    public function getMainButtonsHtml()
    {
        return $this->getRefreshButtonHtml() . parent::getMainButtonsHtml();
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $url = $this->getUrl('*/walmart_productType/edit', [
            'id' => $row->getData('id'),
            'close_on_save' => true,
        ]);

        return sprintf(
            '<a target="_blank" href="%s">%s</a>',
            $url,
            $this->dataHelper->escapeHtml($value)
        );
    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        return sprintf(
            '<a href="javascript:void(0);" onclick="%s">%s</a>',
            $this->makeCallback($value),
            (string)__('Assign')
        );
    }

    private function makeCallback($value)
    {
        return sprintf(
            '%s(this, %s)',
            $this->getMapToTemplateJsFn(),
            $value
        );
    }

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getCreateProductTypeUrl(), 'createProductTypeUrl');

        return parent::_toHtml();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewGrid', [
            '_current' => true,
            '_query' => [
                'marketplace_id' => $this->getMarketplaceId(),
                'map_to_template_js_fn' => $this->getMapToTemplateJsFn(),
                'create_new_template_js_fn' => $this->getCreateNewTemplateJsFn(),
            ],
            'products_ids' => implode(',', $this->getProductsIds()),
        ]);
    }

    private function setNoTemplatesText(): void
    {
        $messageTxt = (string)__('Product Types are not found.');
        $linkTitle = (string)__('Create New Product Type.');

        $createProductTypeUrl = $this->getCreateProductTypeUrl();

        $message = <<<HTML
<p>{$messageTxt} <a href="javascript:void(0);"
    id="create_template_product_type_link"
    onclick="{$this->getCreateNewTemplateJsFn()}('{$createProductTypeUrl}');">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    private function getCreateProductTypeUrl(): string
    {
        return $this->getUrl('*/walmart_productType/edit', [
            'marketplace_id' => $this->marketplaceId,
            'close_on_save' => true,
        ]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    public function getMarketplaceId(): int
    {
        return $this->marketplaceId;
    }

    public function getMapToTemplateJsFn(): string
    {
        return $this->mapToTemplateJsFn;
    }

    public function setMapToTemplateJsFn(string $mapToTemplateLink): void
    {
        $this->mapToTemplateJsFn = $mapToTemplateLink;
    }

    public function getCreateNewTemplateJsFn(): string
    {
        return $this->createNewTemplateJsFn;
    }

    public function setCreateNewTemplateJsFn(string $createNewTemplateJsFn): void
    {
        $this->createNewTemplateJsFn = $createNewTemplateJsFn;
    }

    public function getProductsIds(): array
    {
        return $this->productsIds;
    }
}
