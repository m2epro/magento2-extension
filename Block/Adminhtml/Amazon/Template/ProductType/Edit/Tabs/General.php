<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var array */
    private $formData = [];
    /** @var array */
    private $marketplaceData = [];

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $productType */
    private $productType;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\BuilderFactory */
    private $productTypeBuilderFactory;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Component\Amazon $amazonHelper
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType $productType
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\BuilderFactory $productTypeBuilderFactory
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\BuilderFactory $productTypeBuilderFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->amazonHelper = $amazonHelper;
        $this->productType = $productType;
        $this->productTypeBuilderFactory = $productTypeBuilderFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('amazonTemplateProductTypeEditTabsGeneral');

        $this->formData = $this->getFormData();
        $marketplaces = $this->amazonHelper->getMarketplacesAvailableForAsinCreation();
        $marketplaces = $marketplaces->toArray();
        $this->marketplaceData = $marketplaces['items'];
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\General
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

        $isEdit = (bool)$this->productType->getId();
        $marketplaceId = $this->productType->getMarketplaceId();
        if (!$marketplaceId) {
            $marketplaceId = $this->getSuggestedMarketplaceId();
        }

        $fieldSet->addField(
            'general_marketplace_id',
            self::SELECT,
            [
                'name' => 'general[marketplace_id]',
                'label' => $this->__('Marketplace'),
                'title' => $this->__('Marketplace'),
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
                'label' => $this->__('Product Type'),
                'required' => true,
                'after_element_html' => $this->getProductTypeEditHtml($isEdit)
            ]
        );

        // ---------------------------------------

        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @return string[]
     */
    public function getFormData(): array
    {
        if ($this->productType->getId()) {
            return $this->productType->getData();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\Builder $builder */
        $builder = $this->productTypeBuilderFactory->create();
        return $builder->getDefaultData();
    }

    protected function _beforeToHtml()
    {
        $isMarketplaceSuggested = $this->getSuggestedMarketplaceId() ? 'true' : 'false';

        $this->js->addRequireJs([
            'jQuery' => 'jquery',
            'amazon_template_product_type' => 'M2ePro/Amazon/Template/ProductType',
            'amazon_template_product_type_tabs' => 'M2ePro/Amazon/Template/ProductType/Tabs',
            'amazon_template_product_type_search' => 'M2ePro/Amazon/Template/ProductType/Search',
            'amazon_template_product_type_content' => 'M2ePro/Amazon/Template/ProductType/Content',
            'amazon_template_product_type_finder' => 'M2ePro/Amazon/Template/ProductType/Finder',

        ], <<<JS

        window.AmazonTemplateProductTypeTabsObj = new AmazonTemplateProductTypeTabs();
        window.AmazonTemplateProductTypeContentObj = new AmazonTemplateProductTypeContent();
        window.AmazonTemplateProductTypeObj = new AmazonTemplateProductType();
        window.AmazonTemplateProductTypeSearchObj = new AmazonTemplateProductTypeSearch();
        window.AmazonTemplateProductTypeFinderObj = new AmazonTemplateProductTypeFinder();


        jQuery(function() {
            AmazonTemplateProductTypeObj.initObservers();
            if ($isMarketplaceSuggested) {
                window.AmazonTemplateProductTypeObj.onChangeMarketplaceId();
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

    /**
     * @return array[]
     */
    private function getMarketplaceDataOptions(): array
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']]
        ];

        foreach ($this->marketplaceData as $marketplace) {
            $optionsResult[] = [
                'value' => $marketplace['id'],
                'label' => $this->__($marketplace['title'])
            ];
        }

        return $optionsResult;
    }

    /**
     * @param bool $isEdit
     *
     * @return string
     */
    private function getProductTypeEditHtml(bool $isEdit): string
    {
        $textNotSelected = $this->__('Not Selected');
        $textEdit = $this->__('Edit');

        $title = $isEdit ? $this->productType->getTitle() : '';
        $quotedTitle = $this->dataHelper->escapeHtml($title);
        $displayModeNotSelected = $isEdit ? 'none' : 'inline-block';
        $displayModeTitle = $isEdit ? 'inline-block' : 'none';

        $productTypeNick = $this->productType->getNick();
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

    /**
     * @return int
     */
    private function getSuggestedMarketplaceId(): int
    {
        return (int)$this->getRequest()->getParam('marketplace_id', 0);
    }
}
