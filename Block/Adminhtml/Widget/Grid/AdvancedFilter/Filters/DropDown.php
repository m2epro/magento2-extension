<?php

namespace Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters;

class DropDown implements FilterInterface
{
    /** @var \Magento\Framework\Escaper */
    private $escaper;
    /** @var string */
    private $id;
    /** @var string */
    private $label;
    /** @var DropDown\OptionCollection */
    private $optionsCollection;
    /** @var \Closure */
    private $filterCallback;

    /** @var \Magento\Backend\Block\Widget\Grid|null */
    private $grid = null;
    /** @var bool */
    private $isSelectedValue = false;

    public function __construct(
        \Magento\Framework\Escaper $escaper,
        string $id,
        string $label,
        DropDown\OptionCollection $options,
        \Closure $filterCallback
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->optionsCollection = $options;
        $this->filterCallback = $filterCallback;
        $this->escaper = $escaper;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setGrid(\Magento\Backend\Block\Widget\Grid $grid): void
    {
        $this->grid = $grid;
    }

    public function setSelectedValue(string $value): void
    {
        foreach ($this->optionsCollection->getOptions() as $option) {
            if ($option->getValue() === $value) {
                $option->setIsSelected();
                $this->isSelectedValue = true;
            }
        }
    }

    public function isSelected(): bool
    {
        return $this->isSelectedValue;
    }

    public function getSelectedHtml(): string
    {
        $html = sprintf(
            "<span>%s</span>: <span>%s</span>",
            $this->escaper->escapeHtml($this->getLabel()),
            $this->escaper->escapeHtml($this->getReadableValue())
        );
        $html .= $this->getRemoveButtonHtml();

        return $html;
    }

    public function getFilterHtml(): string
    {
        $html = '<fieldset class="admin__form-field">';
        $html .= sprintf(
            '<legend class="admin__form-field-legend"><span>%s</span></legend>',
            $this->escaper->escapeHtml($this->getLabel())
        );

        $html .= sprintf(
            '<select name="%s" id="%s" class="%s">%s</select>',
            $this->escaper->escapeHtmlAttr($this->getId()),
            $this->escaper->escapeHtmlAttr($this->getHtmlId()),
            'admin__control-select',
            '<option value=""></option>' . $this->optionsCollection->getOptionsHtml()
        );

        $html .= '</fieldset>';

        return $html;
    }

    public function getFilterCallback(): \Closure
    {
        return $this->filterCallback;
    }

    private function getReadableValue(): string
    {
        foreach ($this->optionsCollection->getOptions() as $option) {
            if ($option->isSelected() === false) {
                continue;
            }

            if ($option->isDefaultOptionGroupLabel()) {
                return $option->getLabel();
            }

            return $option->getOptionGroupLabel() . ' > ' . $option->getLabel();
        }

        return '';
    }

    private function getHtmlId(): string
    {
        if ($this->grid === null) {
            return $this->escaper->escapeHtmlAttr($this->getId());
        }

        return sprintf(
            '%s_filter_%s',
            $this->escaper->escapeHtmlAttr($this->grid->getHtmlId()),
            $this->escaper->escapeHtmlAttr($this->getId())
        );
    }

    private function getRemoveButtonHtml(): string
    {
        $html = '<button class="action-remove" type="button" data-id="' . $this->getId() . '">';
        $html .= '<span>' . __('Remove') . '</span>';
        $html .= '</button>';

        return $html;
    }
}
