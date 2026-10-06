<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AdvancedFilter;

abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private \Ess\M2ePro\Model\Listing $listing;

    public function __construct(
        \Ess\M2ePro\Model\Listing $listing,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->listing = $listing;
    }

    public function _construct(): void
    {
        parent::_construct();

        $this->setId('listingAutoActionModeAdvancedFilterGrid');

        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    public function getRowUrl($item): string
    {
        return '';
    }

    public function getGridUrl(): string
    {
        return '';
    }

    /**
     * @param mixed $value
     * @param \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter $row
     * @param \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
     * @param bool $isExport
     */
    public function callbackColumnFilters($value, $row, $column, $isExport): string
    {
        $condition = json_decode($row->getCondition(), true);

        return $this->renderMagentoConditionsHtml($condition);
    }

    /**
     * @param mixed $value
     * @param \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter $row
     * @param \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
     * @param bool $isExport
     */
    public function callbackColumnActions($value, $row, $column, $isExport): string
    {
        $actions = $column->getData('actions');
        if (empty($actions)) {
            return (string)__('Actions Not Set');
        }

        $advancedFilterId = (int)$row->getId();
        $optionsHtml = '<option></option>';
        foreach ($actions as $option) {
            $optionsHtml .= sprintf(
                '<option value="%s">%s</option>',
                $option['value'],
                $option['label']
            );
        }

        $selectClass = "admin__control-select";
        $selectStyles = 'margin: auto; display: block;';
        $selectOnChangeFunction = sprintf('ListingAutoActionObj[this.value](%s);', $advancedFilterId);

        $selectHtml = sprintf(
            '<select class="%s" style="%s" onchange="%s">%s</select>',
            $selectClass,
            $selectStyles,
            $selectOnChangeFunction,
            $optionsHtml
        );

        return sprintf(
            '<div style="padding: 5px;">%s</div>',
            $selectHtml
        );
    }

    protected function _prepareMassaction(): self
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        return $this;
    }

    protected function getListing(): \Ess\M2ePro\Model\Listing
    {
        return $this->listing;
    }

    private function renderMagentoConditionsHtml(array $node): string
    {
        $operators = [
            '==' => __('is'),
            '!=' => __('is not'),
            '>=' => __('equals or greater than'),
            '<=' => __('equals or less than'),
            '>' => __('greater than'),
            '<' => __('less than'),
            '{}' => __('contains'),
            '!{}' => __('does not contain'),
            '()' => __('is one of'),
            '!()' => __('is not one of'),
            '??' => __('is empty'),
            '!??' => __('is not empty'),
        ];

        $html = '';

        $rulePrefix = \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter::RULE_MODEL_PREFIX;
        if (isset($node['aggregator'])) {
            $aggregator = strtoupper($node['aggregator']);
            $label = __('If <strong>%aggregator</strong> of these Conditions are <strong>%aggregator_value</strong>', [
                'aggregator' => $aggregator,
                'aggregator_value' => $node['value'] ? __('TRUE') : __('FALSE'),
            ]);

            $html .= "<div>";
            $html .= "<span>$label</span>";

            if (
                !empty($node[$rulePrefix])
                && is_array($node[$rulePrefix])
            ) {
                $html .= "<ul class='rule-list'>";
                foreach ($node[$rulePrefix] as $childNode) {
                    $html .= "<li>" . $this->renderMagentoConditionsHtml($childNode) . "</li>";
                }
                $html .= "</ul>";
            }
            $html .= "</div>";
        } elseif (isset($node['attribute'])) {
            $attributeCode = $this->_escaper->escapeHtml($node['attribute']);
            $operatorCode = $node['operator'];
            $operator = $operators[$operatorCode] ?? $operatorCode;
            $value = $node['value'];

            if (is_array($value)) {
                $value = implode(
                    ', ',
                    array_map(
                        fn($item) => $this->_escaper->escapeHtml($item),
                        $value
                    )
                );
            } else {
                $value = $this->_escaper->escapeHtml($value);
            }

            $html .= "<div class='rule-item'>";
            $html .= "<span class='rule-attribute'>$attributeCode</span> ";
            $html .= "<span class='rule-operator' style='font-weight: bold'>$operator</span> ";
            $html .= "<span class='rule-value'>$value</span>";
            $html .= "</div>";
        }

        return $html;
    }
}
