<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductType;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory as ProductTypeCollectionFactory;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var int */
    protected $marketplaceId;
    /** @var array */
    protected $productsIds;
    /** @var string */
    private $mapToTemplateJsFn = 'ListingGridObj.templateProductTypeHandler.mapToProductType';
    /** @var string */
    private $createNewTemplateJsFn = 'ListingGridObj.templateProductTypeHandler.createInNewTab';
    /** @var bool */
    private $checkNewAsinAccepted;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory */
    private $productTypeCollectionFactory;

    /**
     * @param ProductTypeCollectionFactory $productTypeCollectionFactory
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplateProductTypeGrid');

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility();
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getMarketplaceId(): int
    {
        return $this->marketplaceId;
    }

    /**
     * @param int $marketplaceId
     */
    public function setMarketplaceId(int $marketplaceId): void
    {
        $this->marketplaceId = $marketplaceId;
    }

    /**
     * @return string
     */
    public function getMapToTemplateJsFn(): string
    {
        return $this->mapToTemplateJsFn;
    }

    /**
     * @param string $mapToTemplateLink
     */
    public function setMapToTemplateJsFn(string $mapToTemplateLink): void
    {
        $this->mapToTemplateJsFn = $mapToTemplateLink;
    }

    /**
     * @return string
     */
    public function getCreateNewTemplateJsFn(): string
    {
        return $this->createNewTemplateJsFn;
    }

    /**
     * @param string $createNewTemplateJsFn
     */
    public function setCreateNewTemplateJsFn(string $createNewTemplateJsFn): void
    {
        $this->createNewTemplateJsFn = $createNewTemplateJsFn;
    }

    /**
     * @param bool $checkNewAsinAccepted
     */
    public function setCheckNewAsinAccepted(bool $checkNewAsinAccepted): void
    {
        $this->checkNewAsinAccepted = $checkNewAsinAccepted;
    }

    /**
     * @return bool
     */
    public function getCheckNewAsinAccepted(): bool
    {
        return $this->checkNewAsinAccepted;
    }

    /**
     * @param array $productsIds
     */
    public function setProductsIds(array $productsIds): void
    {
        $this->productsIds = $productsIds;
    }

    /**
     * @return array
     */
    public function getProductsIds(): array
    {
        return $this->productsIds;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductType\Grid
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareCollection()
    {
        $this->setNoTemplatesText();

        $collection = $this->productTypeCollectionFactory->create();
        $collection->appendFilterMarketplaceId($this->getMarketplaceId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header' => $this->__('Title'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'product_type_title',
            'filter_index' => 'adpt.title',
            'escape' => false,
            'sortable' => true,
            'frame_callback' => [$this, 'callbackColumnTitle'],
        ]);

        $this->addColumn('action', [
            'header' => $this->__('Action'),
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
                     'label' => $this->__('Refresh'),
                     'class' => 'action primary',
                     'onclick' => "ListingGridObj.templateProductTypeHandler.loadGrid()",
                 ])
        );

        return parent::_prepareLayout();
    }

    //########################################

    public function getRefreshButtonHtml()
    {
        return $this->getChildHtml('refresh_button');
    }

    //########################################

    public function getMainButtonsHtml()
    {
        return $this->getRefreshButtonHtml() . parent::getMainButtonsHtml();
    }

    //########################################

    /**
     * @param string $value
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType $row
     * @param \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
     * @param bool $isExport
     *
     * @return string
     */
    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        return sprintf(
            '<a target="_blank" href="%s">%s</a>',
            $this->getEditProductTypeUrl((int)$row->getData('id')),
            $this->dataHelper->escapeHtml($value)
        );
    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        return sprintf(
            '<a href="javascript:void(0);" onclick="%s">%s</a>',
            $this->makeCallback($value),
            $this->__('Assign')
        );
    }

    private function makeCallback($value)
    {
        $mapToAsin = '';
        if ($this->getCheckNewAsinAccepted()) {
            $mapToAsin = ',1';
        }

        return sprintf(
            '%s(this, %s%s)',
            $this->getMapToTemplateJsFn(),
            $value,
            $mapToAsin
        );
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getCreateProductTypeUrl(), 'createProductTypeUrl');

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewGrid', [
            '_current' => true,
            '_query' => [
                'marketplace_id' => $this->getMarketplaceId(),
                'check_is_new_asin_accepted' => $this->getCheckNewAsinAccepted(),
                'map_to_template_js_fn' => $this->getMapToTemplateJsFn(),
                'create_new_template_js_fn' => $this->getCreateNewTemplateJsFn(),
            ],
            'products_ids' => implode(',', $this->getProductsIds()),
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    /**
     * @return void
     */
    protected function setNoTemplatesText(): void
    {
        $messageTxt = $this->__('Product Types are not found.');
        $linkTitle = $this->__('Create New Product Type.');

        $createProductTypeUrl = $this->getCreateProductTypeUrl();

        $message = <<<HTML
<p>{$messageTxt} <a href="javascript:void(0);"
    id="create_template_product_type_link"
    onclick="{$this->getCreateNewTemplateJsFn()}('{$createProductTypeUrl}');">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    /**
     * @return string
     */
    protected function getCreateProductTypeUrl(): string
    {
        return $this->getUrl('*/amazon_template_productType/edit', [
            'marketplace_id' => $this->marketplaceId,
            'close_on_save' => true,
        ]);
    }

    /**
     * @param int $productTypeId
     *
     * @return string
     */
    protected function getEditProductTypeUrl(int $productTypeId): string
    {
        return $this->getUrl('*/amazon_template_productType/edit', [
            'id' => $productTypeId,
            'close_on_save' => true,
        ]);
    }

    //########################################
}
