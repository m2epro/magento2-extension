<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AdvancedFilter;

abstract class AbstractForm extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private ?\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter $advancedFilter;
    private \Ess\M2ePro\Model\Magento\Product\RuleFactory $ruleFactory;

    public function __construct(
        ?\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter $advancedFilter,
        \Ess\M2ePro\Model\Magento\Product\RuleFactory $ruleFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->advancedFilter = $advancedFilter;
        $this->ruleFactory = $ruleFactory;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->setId('listingAutoActionModeAdvancedFilterForm');
    }

    protected function _toHtml(): string
    {
        return sprintf('<div id="advanced_filter_child_data_container">%s</div>', parent::_toHtml());
    }

    protected function hasAdvancedFilter(): bool
    {
        return $this->advancedFilter !== null;
    }

    protected function getAdvancedFilter(): ?\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter
    {
        return $this->advancedFilter;
    }

    protected function addAdvancedFilterIdField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
    {
        $fieldValue = null;
        if ($this->hasAdvancedFilter()) {
            $fieldValue = $this->getAdvancedFilter()->getId();
        }

        $fieldset->addField(
            'advanced_filter_id',
            'hidden',
            [
                'name' => 'advanced_filter_id',
                'value' => $fieldValue,
            ]
        );
    }

    protected function addAutoModeField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
    {
        $fieldset->addField(
            'auto_mode',
            'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_ADVANCED_FILTER,
            ]
        );
    }

    protected function addTitleField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
    {
        $fieldValue = '';
        if ($this->hasAdvancedFilter()) {
            $fieldValue = $this->getAdvancedFilter()->getTitle();
        }

        $fieldset->addField(
            'title',
            'text',
            [
                'label' => __('Title'),
                'class' => 'M2ePro-validate-advanced-filter-title',
                'required' => true,
                'style' => 'width: 350px;',
                'value' => $fieldValue,
            ]
        );
    }

    protected function addConditionField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
    {
        $ruleModel = $this->ruleFactory->create(\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter::RULE_MODEL_PREFIX);
        if ($this->hasAdvancedFilter() && !empty($this->getAdvancedFilter()->getCondition())) {
            $ruleModel->loadFromSerialized($this->advancedFilter->getCondition());
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule $ruleBlock */
        $ruleBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule::class)
            ->setData(['rule_model' => $ruleModel]);

        $fieldset->addField(
            'condition',
            self::CUSTOM_CONTAINER,
            [
                'container_class' => 'M2ePro-validate-advanced-filter-condition',
                'label' => __('Conditions'),
                'text' => $ruleBlock->toHtml(),
            ]
        );
    }
}
