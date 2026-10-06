<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\AdvancedFilter;

class Form extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AdvancedFilter\AbstractForm
{
    private \Ess\M2ePro\Model\Listing $listing;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $amazonTemplateProductTypeRepository;
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $amazonTemplateProductTypeRepository,
        \Ess\M2ePro\Helper\Data $dataHelper,
        ?\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter $advancedFilter,
        \Ess\M2ePro\Model\Magento\Product\RuleFactory $ruleFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct(
            $advancedFilter,
            $ruleFactory,
            $context,
            $registry,
            $formFactory,
            $data
        );
        $this->listing = $listing;
        $this->amazonTemplateProductTypeRepository = $amazonTemplateProductTypeRepository;
        $this->dataHelper = $dataHelper;
    }

    protected function _prepareForm(): self
    {
        $form = $this->_formFactory->create();

        $hiddenFieldset = $form->addFieldset('hidden_fieldset', []);
        $this->addAdvancedFilterIdField($hiddenFieldset);
        $this->addAutoModeField($hiddenFieldset);

        $generalFieldset = $form->addFieldset('category_form_container_field', []);
        $this->addTitleField($generalFieldset);
        $this->addAddingModeField($generalFieldset);
        $this->addAddingAddNotVisibleField($generalFieldset);
        $this->addAutoActionCreateAsinField($generalFieldset);
        $this->addAddingProductTypeTemplateIdField($generalFieldset);
        $this->addDeletingModeField($generalFieldset);
        $this->addConditionField($generalFieldset);

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    protected function _afterToHtml($html): string
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Listing::class)
        );

        $this->js->add(
            <<<JS

        $('adding_mode')
            .observe('change', ListingAutoActionObj.addingModeChange)
            .simulate('change');

        $('auto_action_create_asin')
            .observe('change', ListingAutoActionObj.createAsinChange)
            .simulate('change');

        $('adding_product_type_template_id').observe('change', function(el) {
            var options = $(el.target).select('.empty');
            options.length > 0 && options[0].hide();
        });
JS
        );

        return parent::_afterToHtml($html);
    }

    private function addAddingModeField(\Magento\Framework\Data\Form\Element\Fieldset $generalFieldset)
    {
        $addingModeValue = \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE;
        if ($this->hasAdvancedFilter()) {
            $addingModeValue = $this->getAdvancedFilter()->getAddingMode();
        }

        $generalFieldset->addField(
            'adding_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'adding_mode',
                'label' => __('Products meet the filter conditions'),
                'title' => __('Products meet the filter conditions'),
                'values' => [
                    \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE => __('No Action'),
                    \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD => __('Add to the Listing'),
                ],
                'value' => $addingModeValue,
                'tooltip' => __('Action which will be applied automatically.'),
                'style' => 'width: 350px',
            ]
        );
    }

    private function addAddingAddNotVisibleField(\Magento\Framework\Data\Form\Element\Fieldset $generalFieldset)
    {
        $addingAddNotVisibleValue = \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES;
        if ($this->hasAdvancedFilter()) {
            $addingAddNotVisibleValue = $this->getAdvancedFilter()->getAddingAddNotVisible();
        }

        $addingAddNotVisibleTooltip = __(
            'Set to <strong>Yes</strong> if you want the Magento Products with Visibility ' .
            '\'Not visible Individually\' to be added to the Listing Automatically.<br/>' .
            'If set to <strong>No</strong>, only Variation (i.e. Parent) Magento Products will be ' .
            'added to the Listing Automatically, excluding Child Products.'
        );

        $generalFieldset->addField(
            'adding_add_not_visible',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'adding_add_not_visible',
                'label' => __('Add not Visible Individually Products'),
                'title' => __('Add not Visible Individually Products'),
                'values' => [
                    \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO => __('No'),
                    \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES => __('Yes'),
                ],
                'value' => $addingAddNotVisibleValue,
                'field_extra_attributes' => 'id="adding_add_not_visible_field"',
                'tooltip' => $addingAddNotVisibleTooltip,
            ]
        );
    }

    private function addAutoActionCreateAsinField(\Magento\Framework\Data\Form\Element\Fieldset $generalFieldset)
    {
        $autoActionCreateAsinValue = \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_NO;
        if ($this->hasAdvancedFilter()) {
            /** @var ?\Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter $amazonAdvancedFilter */
            $amazonAdvancedFilter = $this->getAdvancedFilter()->getChildObject();
            if (!empty($amazonAdvancedFilter->getAddingProductTypeTemplateId())) {
                $autoActionCreateAsinValue = \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_YES;
            }
        }

        $autoActionCreateAsinTooltip = __(
            'Should M2E Pro try to create new ASIN/ISBN in case Search ' .
            'Settings are not set or contain the incorrect values?'
        );

        $generalFieldset->addField(
            'auto_action_create_asin',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'auto_action_create_asin',
                'label' => __('Create New ASIN / ISBN if not found'),
                'title' => __('Create New ASIN / ISBN if not found'),
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_NO => __('No'),
                    \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_YES => __('Yes'),
                ],
                'value' => $autoActionCreateAsinValue,
                'field_extra_attributes' => 'id="auto_action_amazon_add_and_create_asin"',
                'tooltip' => $autoActionCreateAsinTooltip,
            ]
        );
    }

    private function addAddingProductTypeTemplateIdField(\Magento\Framework\Data\Form\Element\Fieldset $generalFieldset)
    {
        $addingProductTypeTemplateIdValue = null;
        if ($this->hasAdvancedFilter()) {
            /** @var ?\Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter $amazonAdvancedFilter */
            $amazonAdvancedFilter = $this->getAdvancedFilter()->getChildObject();
            if (!empty($amazonAdvancedFilter->getAddingProductTypeTemplateId())) {
                $addingProductTypeTemplateIdValue = $amazonAdvancedFilter->getAddingProductTypeTemplateId();
            }
        }

        $productTypeTemplates = $this->amazonTemplateProductTypeRepository
            ->findByMarketplaceId($this->listing->getMarketplaceId());

        $options = [
            [
                'label' => '',
                'value' => '',
                'attrs' => ['class' => 'empty'],
            ],
        ];

        foreach ($productTypeTemplates as $template) {
            $options[] = [
                'label' => $this->_escaper->escapeHtml($template->getTitle()),
                'value' => $template->getId(),
            ];
        }

        $addNewProductTypeUrl = $this->getUrl('*/amazon_template_productType/edit', [
            'is_new_asin_accepted' => 1,
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'close_on_save' => true,
        ]);

        $tooltipHtml = $this->getTooltipHtml(
            __(
                'Creation of new ASIN/ISBN will be performed based on specified Product Type. ' .
                'Only the Product Types set for new ASIN/ISBN creation are available for choosing.<br/><br/>' .
                '<b>Note:</b> If chosen Product Type doesn’t meet all the Conditions for new ASIN/ISBN creation, ' .
                'the Products will still be added to M2E Pro Listings but will not be Listed on Amazon.'
            )
        );

        $addProductTypeStyles = 'vertical-align: inherit; margin-left: 65px;';
        $addProductTypeOnclick = sprintf(
            "ListingAutoActionObj.addNewProductType('%s', ListingAutoActionObj.reloadProductTypeTemplates);",
            $addNewProductTypeUrl,
        );

        $addProductTypeLinkHtml = sprintf(
            '<a href="javascript: void(0);" style="%s" onclick="%s">%s</a>',
            $addProductTypeStyles,
            $addProductTypeOnclick,
            __('Add New')
        );

        $generalFieldset->addField(
            'adding_product_type_template_id',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'adding_product_type_template_id',
                'label' => __('Product Type'),
                'title' => __('Product Type'),
                'values' => $options,
                'value' => $addingProductTypeTemplateIdValue,
                'field_extra_attributes' => 'id="auto_action_amazon_add_and_assign_product_type_template"',
                'required' => true,
                'after_element_html' => $tooltipHtml . $addProductTypeLinkHtml,
            ]
        );
    }

    private function addDeletingModeField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
    {
        $deletingModeValue = \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE;
        if ($this->hasAdvancedFilter()) {
            $deletingModeValue = $this->getAdvancedFilter()->getDeletingMode();
        }

        $fieldset->addField(
            'deleting_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'deleting_mode',
                'label' => __('Products no longer meet the filter conditions'),
                'title' => __('Products no longer meet the filter conditions'),
                'values' => [
                    \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE => __('None'),
                    \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP => __('Stop on Channel'),
                    \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE => __(
                        'Stop on Channel and Delete from Listing'
                    ),
                ],
                'value' => $deletingModeValue,
                'style' => 'width: 350px',
            ]
        );
    }
}
