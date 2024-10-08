<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\Tabs;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository;
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Walmart\ProductType $productType;
    private \Ess\M2ePro\Model\Walmart\ProductType\BuilderFactory $productTypeBuilderFactory;
    private array $formData = [];

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        \Ess\M2ePro\Model\Walmart\ProductType $productType,
        \Ess\M2ePro\Model\Walmart\ProductType\BuilderFactory $productTypeBuilderFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->walmartHelper = $walmartHelper;
        $this->productType = $productType;
        $this->productTypeBuilderFactory = $productTypeBuilderFactory;
        $this->marketplaceRepository = $marketplaceRepository;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('walmartProductTypeEditTabsGeneral');

        $this->formData = $this->getFormData();
    }

    protected function _prepareForm(): General
    {
        $form = $this->_formFactory->create();

        // ---------------------------------------

        $form->addField(
            'general_id',
            'hidden',
            [
                'name' => 'general[id]',
                'value' => $this->formData['id']
            ]
        );

        // ---------------------------------------

        $fieldSet = $form->addFieldset(
            'magento_block_product_type_edit_general',
            []
        );

        $isEdit = !$this->productType->isObjectNew();
        $marketplaceId = !$this->productType->isObjectNew()
            ? $this->productType->getMarketplaceId()
            : $this->getSuggestedMarketplaceId();

        $fieldSet->addField(
            'general_product_type_title',
            'text',
            [
                'label' => __('Title'),
                'name' => 'general[product_type_title]',
                'value' =>  !$this->productType->isObjectNew()
                    ?  $this->productType->getTitle()
                    : '',
                'style' => 'min-width: 240px',
                'required' => true,
                'class' => 'M2ePro-general-product-type-title',
            ]
        );

        $fieldSet->addField(
            'general_marketplace_id',
            self::SELECT,
            [
                'name' => 'general[marketplace_id]',
                'label' => (string)__('Marketplace'),
                'title' => (string)__('Marketplace'),
                'values' => $this->getMarketplaceDataOptions(),
                'value' => $marketplaceId,
                'class' => 'required-entry',
                'required' => true,
                'disabled' => $isEdit,
                'style' => 'min-width: 240px',
            ]
        );

        $fieldSet->addField(
            'general_product_type_selection',
            'note',
            [
                'label' => (string)__('Product Type'),
                'required' => true,
                'after_element_html' => $this->getProductTypeEditHtml($isEdit)
            ]
        );

        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class));
        $this->jsTranslator->addTranslations([
            'The specified Product Title is already used for other Product Type. Product Type Title must be unique.' => __(
                'The specified Product Title is already used for other Product Type. Product Type Title must be unique.'
            ),
        ]);

        // ---------------------------------------

        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @return string[]
     */
    public function getFormData(): array
    {
        if (!$this->productType->isObjectNew()) {
            return $this->productType->getData();
        }

        return $this->productTypeBuilderFactory
            ->create()
            ->getDefaultData();
    }

    protected function _beforeToHtml()
    {
        $isMarketplaceSuggested = $this->getSuggestedMarketplaceId() ? 'true' : 'false';

        $this->jsUrl->add(
            $this->getUrl('*/walmart_productType/isUniqueTitle'),
            'walmart_productType/isUniqueTitle'
        );

        $this->js->addRequireJs([
            'jQuery' => 'jquery',
            'walmart_productType' => 'M2ePro/Walmart/ProductType',
            'walmart_productType_tabs' => 'M2ePro/Walmart/ProductType/Tabs',
            'walmart_productType_search' => 'M2ePro/Walmart/ProductType/Search',
            'walmart_productType_content' => 'M2ePro/Walmart/ProductType/Content',
            'walmart_productType_finder' => 'M2ePro/Walmart/ProductType/Finder',
        ], <<<JS

        window.WalmartProductTypeTabsObj = new WalmartProductTypeTabs();
        window.WalmartProductTypeContentObj = new WalmartProductTypeContent();
        window.WalmartProductTypeObj = new WalmartProductType();
        window.WalmartProductTypeSearchObj = new WalmartProductTypeSearch();
        window.WalmartProductTypeFinderObj = new WalmartProductTypeFinder();

        jQuery(function() {
            WalmartProductTypeObj.initObservers();
            if ($isMarketplaceSuggested) {
                window.WalmartProductTypeObj.onChangeMarketplaceId();
            }
        });
JS
        );

        $this->css->add(
            <<<CSS
.admin__field-label {
    text-align: left;
}
CSS
        );

        return parent::_beforeToHtml();
    }

    private function getMarketplaceDataOptions(): array
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']]
        ];

        foreach ($this->marketplaceRepository->findActive() as $marketplace) {
            if (
                !$marketplace->getChildObject()
                             ->isSupportedProductType()
            ) {
                continue;
            }

            $optionsResult[] = [
                'value' => $marketplace->getId(),
                'label' => $marketplace->getTitle(),
            ];
        }

        return $optionsResult;
    }

    private function getProductTypeEditHtml(bool $isEdit): string
    {
        $textNotSelected = __('Not Selected');
        $textEdit = __('Edit');

        $title = $isEdit ? $this->getDictionaryTitle() : '';
        $quotedTitle = $this->dataHelper->escapeHtml($title);
        $displayModeNotSelected = $isEdit ? 'none' : 'inline-block';
        $displayModeTitle = $isEdit ? 'inline-block' : 'none';

        $productTypeNick = $isEdit ? $this->productType->getNick() : '';
        $quotedNick = $this->dataHelper->escapeHtml($productTypeNick);

        return <<<HTML
<div style="width: 240px">
    <span id="general_product_type_not_selected"
        class="product_type_nick_not_selected"
        style="display: $displayModeNotSelected;">$textNotSelected</span>
    <span id="general_selected_product_type_title"
        class="product_type_nick"
        style="display: $displayModeTitle;">$quotedTitle</span>

    <a id="product_type_edit_activator"
        style="margin-left: 1rem; display: none;"
        href="javascript: void(0);"">$textEdit</a>

    <input id="general_product_type"
        name="general[nick]"
        value="$quotedNick"
        class="required-entry m2epro-field-without-tooltip"
        type="hidden">
</div>
HTML;
    }

    private function getSuggestedMarketplaceId(): int
    {
        return (int)$this->getRequest()->getParam('marketplace_id', 0);
    }

    private function getDictionaryTitle(): string
    {
        return $this->productType->getDictionary()->getTitle();
    }
}
