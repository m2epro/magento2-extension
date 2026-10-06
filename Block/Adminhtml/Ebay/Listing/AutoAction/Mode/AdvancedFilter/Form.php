<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\AdvancedFilter;

class Form extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AdvancedFilter\AbstractForm
{
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
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
        $this->dataHelper = $dataHelper;
    }

    protected function _prepareForm(): self
    {
        $form = $this->_formFactory->create();

        $hiddenFieldset = $form->addFieldset('hidden_fieldset', []);
        $this->addAdvancedFilterIdField($hiddenFieldset);
        $this->addAutoModeField($hiddenFieldset);

        $generalFieldset = $form->addFieldset('fieldset_container', []);
        $this->addTitleField($generalFieldset);
        $this->addAddingModeField($generalFieldset);
        $this->addAddingAddNotVisibleField($generalFieldset);
        $this->addDeletingModeField($generalFieldset);
        $this->addConditionField($generalFieldset);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _afterToHtml($html): string
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Listing::class)
        );

        $this->js->add(
            <<<JS
            $('adding_mode')
                .observe('change', ListingAutoActionObj.advancedFilterAddingMode)
                .simulate('change');
JS
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml(): string
    {
        return parent::_toHtml() . '<div id="ebay_category_chooser"></div>';
    }

    private function addAddingModeField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
    {
        $addingModeValue = \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE;
        if ($this->hasAdvancedFilter()) {
            $addingModeValue = $this->getAdvancedFilter()->getAddingMode();
        }

        $fieldset->addField(
            'adding_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'adding_mode',
                'label' => __('Products meet the filter conditions'),
                'title' => __('Products meet the filter conditions'),
                'style' => 'width: 350px;',
                'values' => [
                    \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE => __('No Action'),
                    \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY => __(
                        'Add to the Listing and Assign eBay Category'
                    ),
                ],
                'value' => $addingModeValue,
            ]
        );
    }

    private function addAddingAddNotVisibleField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
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

        $fieldset->addField(
            'adding_add_not_visible',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'adding_add_not_visible',
                'label' => __('Add not Visible Individually Products'),
                'title' => __('Add not Visible Individually Products'),
                'values' => [
                    [
                        'label' => __('No'),
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO,
                    ],
                    [
                        'label' => __('Yes'),
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
                    ],
                ],
                'value' => $addingAddNotVisibleValue,
                'field_extra_attributes' => 'id="adding_add_not_visible_field"',
                'tooltip' => $addingAddNotVisibleTooltip,
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
                    \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE => __('No Action'),
                    \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP => __('Stop on Channel'),
                    \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE => __(
                        'Stop on Channel and Delete from Listing'
                    ),
                ],
                'value' => $deletingModeValue,
                'style' => 'width: 350px;',
            ]
        );
    }
}
